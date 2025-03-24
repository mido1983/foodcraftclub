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

class CartController extends Controller {
    /**
     * Constructor - registers middleware for authentication
     */
    public function __construct() {
        parent::__construct();
        
        // Check if user is authenticated except for public pages
        $this->registerMiddleware(new AuthMiddleware(['index', 'addToCart', 'removeFromCart', 'updateCart']));
    }
    
    /**
     * Display cart page with products
     * @return string Rendered view
     */
    public function index(): string {
        $this->view->title = 'Shopping Cart';
        
        $cartItems = $this->getCartItems();
        $totalAmount = $this->calculateCartTotal($cartItems);
        
        // Group items by seller
        $itemsBySeller = [];
        $sellerIds = [];
        $sellerProfiles = [];
        $belowMinimumSellers = [];
        
        if (!empty($cartItems)) {
            foreach ($cartItems as $item) {
                $sellerId = $item['seller_id'];
                $sellerIds[] = $sellerId;
                
                if (!isset($itemsBySeller[$sellerId])) {
                    $itemsBySeller[$sellerId] = [
                        'seller_id' => $sellerId,
                        'seller_name' => $item['seller_name'],
                        'items' => [],
                        'subtotal' => 0
                    ];
                }
                
                $itemsBySeller[$sellerId]['items'][] = $item;
                $itemsBySeller[$sellerId]['subtotal'] += $item['price'] * $item['quantity'];
            }
            
            // Get seller profiles for minimum order amounts
            if (!empty($sellerIds)) {
                $sellerProfiles = $this->getSellerProfiles(array_unique($sellerIds));
                
                // Check minimum order amounts
                foreach ($itemsBySeller as $sellerId => $sellerData) {
                    if (isset($sellerProfiles[$sellerId]) && 
                        $sellerProfiles[$sellerId]['min_order_amount'] > 0 && 
                        $sellerData['subtotal'] < $sellerProfiles[$sellerId]['min_order_amount']) {
                        $belowMinimumSellers[$sellerId] = [
                            'seller_name' => $sellerProfiles[$sellerId]['name'],
                            'min_order_amount' => $sellerProfiles[$sellerId]['min_order_amount'],
                            'current_amount' => $sellerData['subtotal'],
                            'missing_amount' => $sellerProfiles[$sellerId]['min_order_amount'] - $sellerData['subtotal']
                        ];
                    }
                }
            }
        }
        
        return $this->render('cart/index', [
            'cartItems' => $cartItems,
            'itemsBySeller' => $itemsBySeller,
            'sellerProfiles' => $sellerProfiles,
            'belowMinimumSellers' => $belowMinimumSellers,
            'totalAmount' => $totalAmount
        ]);
    }
    
    /**
     * Add product to cart
     * @return void
     */
    public function addToCart(): void {
        $request = Application::$app->request;
        $response = Application::$app->response;
        $session = Application::$app->session;
        
        if (!$request->isAjax()) {
            $response->setStatusCode(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $data = $request->getJson();
        
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            $response->setStatusCode(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        
        $productId = (int)$data['product_id'];
        $quantity = (int)$data['quantity'];
        
        if ($quantity <= 0) {
            $quantity = 1;
        }
        
        // Get product details
        $product = $this->getProductById($productId);
        
        if (!$product) {
            $response->setStatusCode(404);
            echo json_encode(['success' => false, 'message' => 'Товар не найден']);
            return;
        }
        
        // Check if product is available
        if ($product['is_active'] != 1 && $product['available_for_preorder'] != 1) {
            $response->setStatusCode(400);
            echo json_encode(['success' => false, 'message' => 'Товар не доступен']);
            return;
        }
        
        // Проверяем доступное количество товара
        if ($quantity > $product['quantity']) {
            $response->setStatusCode(400);
            echo json_encode([
                'success' => false, 
                'message' => "Недостаточное количество товара. Доступно: {$product['quantity']}"
            ]);
            return;
        }
        
        // Get current cart
        $cart = $session->get('cart') ?? [];
        
        // Check if product already in cart
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'product_name' => $product['product_name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image_url' => $product['main_image'] ?? '',
                'seller_id' => $product['seller_profile_id'],
                'seller_name' => $product['seller_name'] ?? ''
            ];
        }
        
        // Save cart to session
        $session->set('cart', $cart);
        
        // Calculate new cart total
        $cartItems = $this->getCartItems();
        $totalAmount = $this->calculateCartTotal($cartItems);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart',
            'cart_count' => count($cart),
            'cart_total' => $totalAmount
        ]);
    }
    
    /**
     * Remove product from cart
     * @return void
     */
    public function removeFromCart(): void {
        $request = Application::$app->request;
        $response = Application::$app->response;
        $session = Application::$app->session;
        
        // Добавляем логирование для отладки
        error_log('Remove from cart request: ' . json_encode($request->getJson()));
        
        if (!$request->isAjax()) {
            $response->setStatusCode(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $data = $request->getJson();
        error_log('Request data: ' . json_encode($data));
        
        if (!isset($data['product_id'])) {
            $response->setStatusCode(400);
            echo json_encode(['success' => false, 'message' => 'Missing product_id parameter']);
            return;
        }
        
        $productId = (int)$data['product_id'];
        
        // Get current cart
        $cart = $session->get('cart') ?? [];
        
        // Remove product from cart
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $session->set('cart', $cart);
            
            // Calculate new cart total
            $cartItems = $this->getCartItems();
            $totalAmount = $this->calculateCartTotal($cartItems);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product removed from cart',
                'cart_count' => count($cart),
                'cart_total' => $totalAmount
            ]);
        } else {
            $response->setStatusCode(404);
            echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
        }
    }
    
    /**
     * Update cart item quantity
     * @return void
     */
    public function updateCart(): void {
        $request = Application::$app->request;
        $response = Application::$app->response;
        $session = Application::$app->session;
        
        if (!$request->isAjax()) {
            $response->setStatusCode(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $data = $request->getJson();
        
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            $response->setStatusCode(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        
        $productId = (int)$data['product_id'];
        $quantity = (int)$data['quantity'];
        
        if ($quantity <= 0) {
            // If quantity is 0 or negative, remove item from cart
            $this->removeFromCart();
            return;
        }
        
        // Проверяем доступное количество товара
        $product = $this->getProductById($productId);
        if (!$product) {
            $response->setStatusCode(404);
            echo json_encode(['success' => false, 'message' => 'Товар не найден']);
            return;
        }
        
        if ($quantity > $product['quantity']) {
            $response->setStatusCode(400);
            echo json_encode([
                'success' => false, 
                'message' => "Недостаточное количество товара. Доступно: {$product['quantity']}"
            ]);
            return;
        }
        
        // Get current cart
        $cart = $session->get('cart') ?? [];
        
        // Update product quantity
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
            $session->set('cart', $cart);
            
            // Calculate new cart total
            $cartItems = $this->getCartItems();
            $totalAmount = $this->calculateCartTotal($cartItems);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cart updated',
                'cart_count' => count($cart),
                'cart_total' => $totalAmount,
                'item_total' => $cart[$productId]['price'] * $quantity
            ]);
        } else {
            $response->setStatusCode(404);
            echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
        }
    }
    
    /**
     * Clear cart
     * @return void
     */
    public function clearCart(): void {
        $session = Application::$app->session;
        $session->remove('cart');
        
        Application::$app->response->redirect('/cart');
    }
    
    /**
     * Get cart items with product details
     * @return array Cart items
     */
    private function getCartItems(): array {
        $session = Application::$app->session;
        $cart = $session->get('cart') ?? [];
        
        if (empty($cart)) {
            return [];
        }
        
        // Get product details for cart items
        $productIds = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $sql = "SELECT p.*, sp.name as seller_name, 
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image,
                    p.quantity as available_quantity
               FROM products p
               LEFT JOIN seller_profiles sp ON p.seller_profile_id = sp.id
               WHERE p.id IN ($placeholders)";
        
        $statement = Application::$app->db->prepare($sql);
        
        // Bind product IDs
        foreach ($productIds as $index => $productId) {
            $statement->bindValue($index + 1, $productId, PDO::PARAM_INT);
        }
        
        $statement->execute();
        $products = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Index products by ID
        $productsById = [];
        foreach ($products as $product) {
            $productsById[$product['id']] = $product;
        }
        
        // Combine cart items with product details
        $cartItems = [];
        foreach ($cart as $productId => $item) {
            if (isset($productsById[$productId])) {
                $cartItems[$productId] = array_merge($item, [
                    'available_quantity' => $productsById[$productId]['available_quantity']
                ]);
            } else {
                // Product no longer exists, remove from cart
                unset($cart[$productId]);
                $session->set('cart', $cart);
            }
        }
        
        return $cartItems;
    }
    
    /**
     * Calculate cart total
     * @param array $cartItems Cart items
     * @return float Total amount
     */
    private function calculateCartTotal(array $cartItems): float {
        $total = 0;
        
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }
    
    /**
     * Get product by ID
     * @param int $productId Product ID
     * @return array|false Product data or false if not found
     */
    private function getProductById(int $productId) {
        $sql = "SELECT p.*, sp.name as seller_name, 
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
               FROM products p
               LEFT JOIN seller_profiles sp ON p.seller_profile_id = sp.id
               WHERE p.id = :product_id";
        
        $statement = Application::$app->db->prepare($sql);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get seller profiles by IDs
     * @param array $sellerIds Seller IDs
     * @return array Seller profiles indexed by ID
     */
    private function getSellerProfiles(array $sellerIds): array {
        if (empty($sellerIds)) {
            return [];
        }
        
        // Filter out any null or invalid values and ensure unique IDs
        $sellerIds = array_filter(array_unique($sellerIds), function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        if (empty($sellerIds)) {
            return [];
        }
        
        // Use named parameters instead of positional parameters
        $placeholders = [];
        $params = [];
        
        foreach ($sellerIds as $i => $id) {
            $paramName = ":seller_id_{$i}";
            $placeholders[] = $paramName;
            $params[$paramName] = $id;
        }
        
        $placeholdersStr = implode(',', $placeholders);
        $sql = "SELECT * FROM seller_profiles WHERE id IN ($placeholdersStr)";
        
        $statement = Application::$app->db->prepare($sql);
        
        // Bind all parameters
        foreach ($params as $param => $value) {
            $statement->bindValue($param, $value, PDO::PARAM_INT);
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
}
