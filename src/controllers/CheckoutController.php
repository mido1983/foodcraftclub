<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Middleware\AuthMiddleware;
use PDO;

class CheckoutController extends Controller {
    /**
     * Constructor - registers middleware for authentication
     */
    public function __construct() {
        parent::__construct();
        
        // All checkout pages require authentication
        $this->registerMiddleware(new AuthMiddleware(['index', 'process', 'success', 'cancel']));
    }
    
    /**
     * Display checkout page
     * @return string Rendered view
     */
    public function index(): string {
        $this->view->title = 'Checkout';
        
        $session = Application::$app->session;
        $cartItems = $session->get('cart') ?? [];
        
        // Redirect to cart if cart is empty
        if (empty($cartItems)) {
            Application::$app->response->redirect('/cart');
            return '';
        }
        
        // Group cart items by seller
        $itemsBySeller = [];
        $totalAmount = 0;
        
        foreach ($cartItems as $item) {
            $sellerId = $item['seller_id'];
            
            if (!isset($itemsBySeller[$sellerId])) {
                $itemsBySeller[$sellerId] = [
                    'seller_id' => $sellerId,
                    'seller_name' => $item['seller_name'],
                    'items' => [],
                    'subtotal' => 0,
                    'delivery_fee' => 0,
                    'total' => 0
                ];
            }
            
            $itemsBySeller[$sellerId]['items'][] = $item;
            $itemSubtotal = $item['price'] * $item['quantity'];
            $itemsBySeller[$sellerId]['subtotal'] += $itemSubtotal;
            $totalAmount += $itemSubtotal;
        }
        
        // Get cities and districts for delivery address
        $cities = $this->getCities();
        $districts = $this->getDistricts();
        
        // Get available payment methods
        $paymentMethods = $this->getPaymentMethods();
        
        // Get seller delivery areas and payment options
        $sellerIds = array_keys($itemsBySeller);
        $sellerDeliveryAreas = $this->getSellerDeliveryAreas($sellerIds);
        $sellerPaymentOptions = $this->getSellerPaymentOptions($sellerIds);
        
        // Get seller profiles for minimum order amounts
        $sellerProfiles = $this->getSellerProfiles($sellerIds);
        
        // Check minimum order amounts
        $belowMinimumSellers = [];
        foreach ($itemsBySeller as $sellerId => $sellerData) {
            if (isset($sellerProfiles[$sellerId]) && $sellerData['subtotal'] < $sellerProfiles[$sellerId]['min_order_amount']) {
                $belowMinimumSellers[] = [
                    'seller_name' => $sellerData['seller_name'],
                    'current_amount' => $sellerData['subtotal'],
                    'min_amount' => $sellerProfiles[$sellerId]['min_order_amount']
                ];
            }
        }
        
        return $this->render('checkout/index', [
            'cartItems' => $cartItems,
            'itemsBySeller' => $itemsBySeller,
            'totalAmount' => $totalAmount,
            'cities' => $cities,
            'districts' => $districts,
            'paymentMethods' => $paymentMethods,
            'sellerDeliveryAreas' => $sellerDeliveryAreas,
            'sellerPaymentOptions' => $sellerPaymentOptions,
            'belowMinimumSellers' => $belowMinimumSellers
        ]);
    }
    
    /**
     * Process checkout
     * @return void
     */
    public function process(): void {
        $request = Application::$app->request;
        $response = Application::$app->response;
        $session = Application::$app->session;
        
        if ($request->isPost()) {
            $data = $request->getBody();
            
            // Validate required fields
            $requiredFields = ['city_id', 'address_line', 'payment_method_id'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                $session->setFlash('error', 'Please fill in all required fields: ' . implode(', ', $missingFields));
                $response->redirect('/checkout');
                return;
            }
            
            // Get cart items
            $cartItems = $session->get('cart') ?? [];
            
            if (empty($cartItems)) {
                $response->redirect('/cart');
                return;
            }
            
            // Group items by seller
            $itemsBySeller = [];
            
            foreach ($cartItems as $item) {
                $sellerId = $item['seller_id'];
                
                if (!isset($itemsBySeller[$sellerId])) {
                    $itemsBySeller[$sellerId] = [
                        'seller_id' => $sellerId,
                        'items' => [],
                        'subtotal' => 0
                    ];
                }
                
                $itemsBySeller[$sellerId]['items'][] = $item;
                $itemsBySeller[$sellerId]['subtotal'] += $item['price'] * $item['quantity'];
            }
            
            // Get seller profiles for minimum order amounts
            $sellerIds = array_keys($itemsBySeller);
            $sellerProfiles = $this->getSellerProfiles($sellerIds);
            
            // Check minimum order amounts
            foreach ($itemsBySeller as $sellerId => $sellerData) {
                if (isset($sellerProfiles[$sellerId]) && 
                    isset($sellerProfiles[$sellerId]['min_order_amount']) && 
                    $sellerData['subtotal'] < $sellerProfiles[$sellerId]['min_order_amount']) {
                    $session->setFlash('error', 'Order amount for seller ' . $sellerProfiles[$sellerId]['name'] . 
                                     ' is below minimum required amount of ' . $sellerProfiles[$sellerId]['min_order_amount']);
                    $response->redirect('/checkout');
                    return;
                }
            }
            
            // Calculate delivery fees
            $cityId = (int)$data['city_id'];
            $districtId = !empty($data['district_id']) ? (int)$data['district_id'] : null;
            $sellerDeliveryAreas = $this->getSellerDeliveryAreas($sellerIds);
            
            // Create orders for each seller
            $db = Application::$app->db;
            $userId = $session->getUserId();
            $paymentMethodId = (int)$data['payment_method_id'];
            $addressLine = $data['address_line'];
            $orderIds = [];
            
            try {
                $db->beginTransaction();
                
                foreach ($itemsBySeller as $sellerId => $sellerData) {
                    // Calculate delivery fee
                    $deliveryFee = 0;
                    if (isset($sellerDeliveryAreas[$sellerId])) {
                        foreach ($sellerDeliveryAreas[$sellerId] as $area) {
                            if ($area['city_id'] == $cityId) {
                                $deliveryFee = $area['delivery_fee'];
                                
                                // Check if order qualifies for free delivery
                                if (isset($area['free_from_amount']) && 
                                    $area['free_from_amount'] !== null && 
                                    $sellerData['subtotal'] >= $area['free_from_amount']) {
                                    $deliveryFee = 0;
                                }
                                
                                break;
                            }
                        }
                    }
                    
                    // Calculate total amount
                    $totalAmount = $sellerData['subtotal'] + $deliveryFee;
                    
                    // Create order
                    $orderSql = "INSERT INTO orders (buyer_id, seller_profile_id, total_amount, status, payment_status, 
                                    payment_method_id, city_id, district_id, address_line, delivery_fee, notes) 
                                  VALUES (:buyer_id, :seller_profile_id, :total_amount, 'new', 'unpaid', 
                                    :payment_method_id, :city_id, :district_id, :address_line, :delivery_fee, :notes)";
                    
                    $orderStmt = $db->prepare($orderSql);
                    $orderStmt->bindValue(':buyer_id', $userId, PDO::PARAM_INT);
                    $orderStmt->bindValue(':seller_profile_id', $sellerId, PDO::PARAM_INT);
                    $orderStmt->bindValue(':total_amount', $totalAmount, PDO::PARAM_STR);
                    $orderStmt->bindValue(':payment_method_id', $paymentMethodId, PDO::PARAM_INT);
                    $orderStmt->bindValue(':city_id', $cityId, PDO::PARAM_INT);
                    $orderStmt->bindValue(':district_id', $districtId, $districtId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $orderStmt->bindValue(':address_line', $addressLine, PDO::PARAM_STR);
                    $orderStmt->bindValue(':delivery_fee', $deliveryFee, PDO::PARAM_STR);
                    $orderStmt->bindValue(':notes', $data['delivery_notes'] ?? null, PDO::PARAM_STR);
                    $orderStmt->execute();
                    
                    $orderId = $db->lastInsertId();
                    $orderIds[] = $orderId;
                    
                    // Save phone number to order_metadata
                    if (!empty($data['phone'])) {
                        try {
                            $metadataSql = "INSERT INTO order_metadata (order_id, phone, delivery_notes) 
                                          VALUES (:order_id, :phone, :delivery_notes)";
                            
                            $metadataStmt = $db->prepare($metadataSql);
                            $metadataStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                            $metadataStmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
                            $metadataStmt->bindValue(':delivery_notes', $data['delivery_notes'] ?? null, PDO::PARAM_STR);
                            $metadataStmt->execute();
                        } catch (\Exception $e) {
                            // Log the error but continue with the order process
                            Application::$app->logger->error('Error saving order metadata: ' . $e->getMessage(), [
                                'order_id' => $orderId,
                                'trace' => $e->getTraceAsString()
                            ], 'errors.log');
                        }
                    }
                    
                    // Create order items
                    foreach ($sellerData['items'] as $item) {
                        $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price_at_moment) 
                                    VALUES (:order_id, :product_id, :quantity, :price_at_moment)";
                        
                        $itemStmt = $db->prepare($itemSql);
                        $itemStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                        $itemStmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
                        $itemStmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
                        $itemStmt->bindValue(':price_at_moment', $item['price'], PDO::PARAM_STR);
                        $itemStmt->execute();
                        
                        // Уменьшаем количество товара в базе данных
                        $updateQuantitySql = "UPDATE products SET quantity = quantity - :ordered_quantity 
                                             WHERE id = :product_id AND quantity >= :ordered_quantity";
                        $updateQuantityStmt = $db->prepare($updateQuantitySql);
                        $updateQuantityStmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
                        $updateQuantityStmt->bindValue(':ordered_quantity', $item['quantity'], PDO::PARAM_INT);
                        $updateQuantityStmt->execute();
                        
                        // Проверяем, было ли обновлено количество товара
                        if ($updateQuantityStmt->rowCount() === 0) {
                            // Получаем текущее количество товара
                            $checkQuantitySql = "SELECT quantity FROM products WHERE id = :product_id";
                            $checkQuantityStmt = $db->prepare($checkQuantitySql);
                            $checkQuantityStmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
                            $checkQuantityStmt->execute();
                            $availableQuantity = $checkQuantityStmt->fetchColumn();
                            
                            // Откатываем транзакцию, если товара недостаточно
                            throw new \Exception("Недостаточное количество товара '{$item['product_name']}'. Доступно: {$availableQuantity}, запрошено: {$item['quantity']}");
                        }
                    }
                }
                
                $db->commit();
                
                // Clear cart after successful order
                $session->remove('cart');
                
                // Store order IDs in session for success page
                $session->set('last_order_ids', $orderIds);
                
                // Redirect to success page
                $response->redirect('/checkout/success');
                
            } catch (\Exception $e) {
                $db->rollBack();
                Application::$app->logger->error('Checkout error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'data' => $data,
                    'user_id' => $userId
                ], 'errors.log');
                $session->setFlash('error', 'An error occurred during checkout. Please try again.');
                $response->redirect('/checkout');
            }
            
        } else {
            $response->redirect('/checkout');
        }
    }
    
    /**
     * Display checkout success page
     * @return string Rendered view
     */
    public function success(): string {
        $this->view->title = 'Order Confirmation';
        
        $session = Application::$app->session;
        $orderIds = $session->get('last_order_ids') ?? [];
        
        if (empty($orderIds)) {
            Application::$app->response->redirect('/cart');
            return '';
        }
        
        // Get order details
        $orders = $this->getOrdersByIds($orderIds);
        
        if (empty($orders)) {
            Application::$app->response->redirect('/cart');
            return '';
        }
        
        // Get the first order for the success page
        $order = $orders[0];
        $orderItems = [];
        
        // Prepare order items with seller information
        foreach ($orders as $orderData) {
            foreach ($orderData['items'] as $item) {
                // Add seller information to each item
                $item['seller_name'] = $orderData['seller_name'];
                $item['seller_id'] = $orderData['seller_profile_id'];
                $item['delivery_fee'] = $orderData['delivery_fee'];
                $orderItems[] = $item;
            }
        }
        
        return $this->render('checkout/success', [
            'order' => $order,
            'orderItems' => $orderItems
        ]);
    }
    
    /**
     * Display checkout cancel page
     * @return string Rendered view
     */
    public function cancel(): string {
        $this->view->title = 'Order Cancelled';
        
        return $this->render('checkout/cancel');
    }
    
    /**
     * Get cities from database based on seller delivery areas
     * @return array Cities
     */
    private function getCities(): array {
        $session = Application::$app->session;
        $cartItems = $session->get('cart') ?? [];
        
        // Extract seller IDs from cart items
        $sellerIds = [];
        foreach ($cartItems as $item) {
            $sellerIds[$item['seller_id']] = $item['seller_id'];
        }
        $sellerIds = array_values($sellerIds);
        
        if (empty($sellerIds)) {
            return [];
        }
        
        // Get cities that sellers deliver to
        $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
        
        $sql = "SELECT DISTINCT c.id, c.city_name 
               FROM cities c
               INNER JOIN seller_delivery_areas sda ON sda.city_id = c.id
               INNER JOIN seller_profiles sp ON sda.seller_profile_id = sp.id
               WHERE sp.id IN ($placeholders)
               ORDER BY c.city_name ASC";
        
        $statement = Application::$app->db->prepare($sql);
        
        // Bind seller IDs
        foreach ($sellerIds as $index => $sellerId) {
            $statement->bindValue($index + 1, $sellerId);
        }
        
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get districts from database
     * @return array Districts
     */
    private function getDistricts(): array {
        $sql = "SELECT d.id, d.district_name, c.id as city_id, c.city_name 
               FROM districts d
               INNER JOIN cities c ON c.district_id = d.id
               ORDER BY c.city_name ASC, d.district_name ASC";
        $statement = Application::$app->db->prepare($sql);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get payment methods from database
     * @return array Payment methods
     */
    private function getPaymentMethods(): array {
        $sql = "SELECT id, method_code, method_name FROM payment_methods ORDER BY id ASC";
        $statement = Application::$app->db->prepare($sql);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get seller delivery areas
     * @param array $sellerIds Seller IDs
     * @return array Delivery areas grouped by seller ID
     */
    private function getSellerDeliveryAreas(array $sellerIds): array {
        if (empty($sellerIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
        
        $sql = "SELECT sda.* 
               FROM seller_delivery_areas sda
               WHERE sda.seller_profile_id IN ($placeholders)";
        
        $statement = Application::$app->db->prepare($sql);
        
        // Bind seller IDs
        foreach ($sellerIds as $index => $sellerId) {
            $statement->bindValue($index + 1, $sellerId, PDO::PARAM_INT);
        }
        
        $statement->execute();
        $areas = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by seller ID
        $result = [];
        foreach ($areas as $area) {
            $sellerId = $area['seller_profile_id'];
            if (!isset($result[$sellerId])) {
                $result[$sellerId] = [];
            }
            $result[$sellerId][] = $area;
        }
        
        return $result;
    }
    
    /**
     * Get seller payment options
     * @param array $sellerIds Seller IDs
     * @return array Payment options grouped by seller ID
     */
    private function getSellerPaymentOptions(array $sellerIds): array {
        if (empty($sellerIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
        
        $sql = "SELECT spo.*, pm.method_code, pm.method_name 
               FROM seller_payment_options spo
               INNER JOIN payment_methods pm ON spo.payment_method_id = pm.id
               WHERE spo.seller_profile_id IN ($placeholders) AND spo.enabled = 1";
        
        $statement = Application::$app->db->prepare($sql);
        
        // Bind seller IDs
        foreach ($sellerIds as $index => $sellerId) {
            $statement->bindValue($index + 1, $sellerId, PDO::PARAM_INT);
        }
        
        $statement->execute();
        $options = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by seller ID
        $result = [];
        foreach ($options as $option) {
            $sellerId = $option['seller_profile_id'];
            if (!isset($result[$sellerId])) {
                $result[$sellerId] = [];
            }
            $result[$sellerId][] = $option;
        }
        
        return $result;
    }
    
    /**
     * Get seller profiles
     * @param array $sellerIds Seller IDs
     * @return array Seller profiles indexed by ID
     */
    private function getSellerProfiles(array $sellerIds): array {
        if (empty($sellerIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
        
        $sql = "SELECT * FROM seller_profiles WHERE id IN ($placeholders)";
        $statement = Application::$app->db->prepare($sql);
        
        // Bind seller IDs
        foreach ($sellerIds as $index => $sellerId) {
            $statement->bindValue($index + 1, $sellerId, PDO::PARAM_INT);
        }
        
        $statement->execute();
        $profiles = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Index by ID
        $result = [];
        foreach ($profiles as $profile) {
            $result[$profile['id']] = $profile;
        }
        
        return $result;
    }
    
    /**
     * Get orders by IDs
     * @param array $orderIds Order IDs
     * @return array Orders with items
     */
    private function getOrdersByIds(array $orderIds): array {
        if (empty($orderIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        
        // Get orders
        $orderSql = "SELECT o.*, sp.name as seller_name, pm.method_name as payment_method_name, pm.method_code as payment_method_code,
                        c.city_name, d.district_name, NOW() as created_at
                     FROM orders o
                     LEFT JOIN seller_profiles sp ON o.seller_profile_id = sp.id
                     LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                     LEFT JOIN cities c ON o.city_id = c.id
                     LEFT JOIN districts d ON o.district_id = d.id
                     WHERE o.id IN ($placeholders)";
        
        $orderStmt = Application::$app->db->prepare($orderSql);
        
        // Bind order IDs
        foreach ($orderIds as $index => $orderId) {
            $orderStmt->bindValue($index + 1, $orderId, PDO::PARAM_INT);
        }
        
        $orderStmt->execute();
        $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get order items
        $itemSql = "SELECT oi.*, p.product_name, p.price as price, pi.image_url
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    LEFT JOIN (SELECT product_id, image_url FROM product_images WHERE is_main = 1) pi ON oi.product_id = pi.product_id
                    WHERE oi.order_id IN ($placeholders)";
        
        $itemStmt = Application::$app->db->prepare($itemSql);
        
        // Bind order IDs
        foreach ($orderIds as $index => $orderId) {
            $itemStmt->bindValue($index + 1, $orderId, PDO::PARAM_INT);
        }
        
        $itemStmt->execute();
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group items by order ID
        $itemsByOrder = [];
        foreach ($items as $item) {
            $orderId = $item['order_id'];
            if (!isset($itemsByOrder[$orderId])) {
                $itemsByOrder[$orderId] = [];
            }
            $itemsByOrder[$orderId][] = $item;
        }
        
        // Add items to orders
        foreach ($orders as &$order) {
            $order['items'] = $itemsByOrder[$order['id']] ?? [];
            // Add phone field if it doesn't exist
            if (!isset($order['phone'])) {
                $order['phone'] = 'Not provided';
            }
            // Add delivery_notes field if it doesn't exist
            if (!isset($order['delivery_notes'])) {
                $order['delivery_notes'] = '';
            }
        }
        
        return $orders;
    }
}
