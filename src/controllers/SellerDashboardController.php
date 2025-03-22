<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Request;
use App\Models\User;
use Exception;
use PDO;

class SellerDashboardController extends DashboardController {
    protected Request $request;
    
    public function __construct() {
        parent::__construct();
        $this->registerMiddleware(new AuthMiddleware([], ['seller']));
        $this->request = Application::$app->request;
    }

    public function index() {
        $this->view->title = 'Seller Dashboard';
        
        Application::$app->logger->info(
            'Request to seller page', 
            ['uri' => $_SERVER['REQUEST_URI']],
            'users.log'
        );
        
        try {
            $user = $this->getUserProfile();
            
            if (!$user) {
                Application::$app->logger->warning(
                    'Attempt to access seller page without authorization', 
                    [],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            Application::$app->logger->info(
                'User accessed seller page', 
                ['user_id' => $user->id, 'roles' => $user->getRoles()],
                'users.log'
            );
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Attempt to access seller page without seller profile', 
                    ['user_id' => $user->id],
                    'errors.log'
                );
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            $checkCategoriesTable = Application::$app->db->prepare("
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'categories'
            ");
            $checkCategoriesTable->execute();
            $categoriesTableExists = $checkCategoriesTable->fetchColumn();
            
            if ($categoriesTableExists) {
                $statement = Application::$app->db->prepare("
                    SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            } else {
                $statement = Application::$app->db->prepare("
                    SELECT p.*, '' as category_name
                    FROM products p
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            }
            
            $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
            $products = $statement->fetchAll();
            
            Application::$app->logger->info(
                'Products found', 
                ['user_id' => $user->id, 'count' => count($products)],
                'users.log'
            );
            
            $categories = [];
            if ($categoriesTableExists) {
                $categoriesStmt = Application::$app->db->prepare("SELECT id, name FROM categories ORDER BY name");
                $categoriesStmt->execute();
                $categories = $categoriesStmt->fetchAll();
            }
            
            return $this->render('seller/dashboard/index', [
                'user' => $user,
                'sellerProfile' => $sellerProfile,
                'products' => $products,
                'categories' => $categories,
                'stats' => $this->getSellerStats($user->id),
                'recentOrders' => $this->getRecentOrders($user->id),
                'notifications' => $this->getNotifications(),
                'unreadNotifications' => $this->getUnreadNotificationsCount()
            ]);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error accessing products page: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'An error occurred while loading the products page. Please try again.');
            
            Application::$app->response->redirect('/seller');
            return '';
        }
    }

    public function products() {
        $this->view->title = 'My Products';
        
        Application::$app->logger->info(
            'Request to products page', 
            ['uri' => $_SERVER['REQUEST_URI']],
            'users.log'
        );
        
        try {
            $user = $this->getUserProfile();
            
            if (!$user) {
                Application::$app->logger->warning(
                    'Attempt to access products page without authorization', 
                    [],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            Application::$app->logger->info(
                'User accessed products page', 
                ['user_id' => $user->id, 'roles' => $user->getRoles()],
                'users.log'
            );
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Attempt to access products page without seller profile', 
                    ['user_id' => $user->id],
                    'errors.log'
                );
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            $checkCategoriesTable = Application::$app->db->prepare("
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'categories'
            ");
            $checkCategoriesTable->execute();
            $categoriesTableExists = $checkCategoriesTable->fetchColumn();
            
            if ($categoriesTableExists) {
                $statement = Application::$app->db->prepare("
                    SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            } else {
                $statement = Application::$app->db->prepare("
                    SELECT p.*, '' as category_name
                    FROM products p
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            }
            
            $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
            $products = $statement->fetchAll();
            
            Application::$app->logger->info(
                'Products found', 
                ['user_id' => $user->id, 'count' => count($products)],
                'users.log'
            );
            
            $categories = [];
            if ($categoriesTableExists) {
                $categoriesStmt = Application::$app->db->prepare("SELECT id, name FROM categories ORDER BY name");
                $categoriesStmt->execute();
                $categories = $categoriesStmt->fetchAll();
            }
            
            return $this->render('seller/dashboard/products', [
                'user' => $user,
                'sellerProfile' => $sellerProfile,
                'products' => $products,
                'categories' => $categories,
                'notifications' => $this->getNotifications(),
                'unreadNotifications' => $this->getUnreadNotificationsCount()
            ]);
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error accessing products page: ' . $e->getMessage(), 
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'An error occurred while loading the products page. Please try again.');
            
            Application::$app->response->redirect('/seller');
            return '';
        }
    }

    protected function getSellerProfile(int $userId): ?array {
        try {
            $statement = Application::$app->db->prepare("
                SELECT * FROM seller_profiles
                WHERE user_id = :user_id
                LIMIT 1
            ");
            $statement->execute(['user_id' => $userId]);
            $profile = $statement->fetch(PDO::FETCH_ASSOC);
            
            return $profile ?: null;
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting seller profile: ' . $e->getMessage(), 
                ['user_id' => $userId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return null;
        }
    }

    protected function getSellerStats(int $userId): array {
        try {
            $sellerProfile = $this->getSellerProfile($userId);
            
            if (!$sellerProfile) {
                return [
                    'total_products' => 0,
                    'total_orders' => 0,
                    'total_sales' => 0,
                    'avg_rating' => 0
                ];
            }
            
            $productsStmt = Application::$app->db->prepare("SELECT COUNT(*) as total FROM products WHERE seller_profile_id = :seller_profile_id");
            $productsStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $totalProducts = $productsStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $ordersStmt = Application::$app->db->prepare("
                SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as total_sales
                FROM orders
                WHERE seller_profile_id = :seller_profile_id
                AND status != 'cancelled'
            ");
            $ordersStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $orderData = $ordersStmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if product_reviews table exists before querying
            $checkTableStmt = Application::$app->db->prepare("SHOW TABLES LIKE 'product_reviews'");
            $checkTableStmt->execute();
            $tableExists = $checkTableStmt->rowCount() > 0;
            
            $avgRating = 0;
            if ($tableExists) {
                $ratingStmt = Application::$app->db->prepare("
                    SELECT AVG(rating) as avg_rating
                    FROM product_reviews
                    WHERE product_id IN (
                        SELECT id FROM products WHERE seller_profile_id = :seller_profile_id
                    )
                ");
                $ratingStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
                $avgRating = $ratingStmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0;
            }
            
            return [
                'total_products' => (int)$totalProducts,
                'total_orders' => (int)($orderData['order_count'] ?? 0),
                'total_sales' => (float)($orderData['total_sales'] ?? 0),
                'avg_rating' => round((float)$avgRating, 1)
            ];
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting seller stats: ' . $e->getMessage(), 
                ['user_id' => $userId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            return [
                'total_products' => 0,
                'total_orders' => 0,
                'total_sales' => 0,
                'avg_rating' => 0
            ];
        }
    }

    protected function getRecentOrders(int $userId): array {
        try {
            $sellerProfile = $this->getSellerProfile($userId);
            
            if (!$sellerProfile) {
                return [];
            }
            
            // Исправленный запрос - используем buyer_id вместо customer_id
            $statement = Application::$app->db->prepare("
                SELECT o.*, u.email as customer_email
                FROM orders o
                JOIN users u ON o.buyer_id = u.id
                WHERE o.seller_profile_id = :seller_profile_id
                ORDER BY o.order_date DESC
                LIMIT 10
            ");
            $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error getting recent orders: ' . $e->getMessage(), 
                ['user_id' => $userId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            return [];
        }
    }

    /**
     * Display the form for creating a new product
     * @return string
     */
    public function newProduct(): string {
        $user = $this->getUserProfile();
        
        if (!$user) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page');
            return Application::$app->response->redirect('/login');
        }
        
        // Get seller profile
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->logger->warning(
                'Attempt to access new product page without seller profile', 
                ['user_id' => $user->id]
            );
            Application::$app->session->setFlash('error', 'You need a seller profile to add products');
            return Application::$app->response->redirect('/seller');
        }
        
        $this->view->title = 'Add New Product';
        return $this->render('seller/products/new', [
            'user' => $user,
            'sellerProfile' => $sellerProfile,
            'categories' => $this->getCategories()
        ]);
    }
    
    /**
     * u041eu0442u043eu0431u0440u0430u0437u0435u043du0438u0435 u0438 u0440u0435u0434u0430u043au0442u0438u0440u043eu0432u0430u043du0438u0435 u043fu0440u043eu0444u0438u043bu044h
     * @return string
     */
    public function profile(): string {
        $user = $this->getUserProfile();
        
        if (!$user) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page');
            return Application::$app->response->redirect('/login');
        }
        
        // u041fu0435u0440u0435u043cu0430u0435u043c u043fu0440u043eu0444u0438u043bu044c u043fu0440u043eu0434u0430u0432u0446u0430
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->logger->warning(
                'Attempt to access seller profile page without seller profile', 
                ['user_id' => $user->id]
            );
            Application::$app->session->setFlash('error', 'You need a seller profile to view this page');
            return Application::$app->response->redirect('/seller');
        }
        
        // u041fu0435u0440u0435u043cu0430u0435u043c u0434u043eu0441u0442u0443u043fu043du044bu0443 u0441u043fu0431u043eu0440u0430 u043eu043fu043bu0430u0442u044b
        $paymentMethods = [];
        $sellerPaymentOptions = [];
        
        try {
            $db = Application::$app->db;
            
            // u0417u0430u043fu0440u043eu0441 u043du0430 u043fu043eu043bu0443u0447u0435u043du0438u0435 u0432u0441u0435u0445 u043au0430u0442u0435u0433u043eu0440u0438u0439
            $methodsStmt = $db->prepare("SELECT * FROM payment_methods ORDER BY method_name");
            $methodsStmt->execute();
            $paymentMethods = $methodsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // u0417u0430u043fu0440u043eu0441 u043du0430 u043fu043eu043bu0443u0447u0435u043du0438u0435 u0432u044bu0431u0440u0430u043du043du044bu0443 u0441u043fu043eu0441u043eu0431u043eu0432 u043eu043fu043bu0430u0442u044b u043fu0440u043eu0434u0430u0432u0446u0430
            $optionsStmt = $db->prepare("SELECT payment_method_id FROM seller_payment_options WHERE seller_profile_id = :seller_profile_id");
            $optionsStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $options = $optionsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // u041fu0440u0435u043eu0431u0440u0430u0437u0443u0435u043c u0432 u043cu0430u0441u0441u0438u0432 ID
            $sellerPaymentOptions = array_map(function($option) {
                return $option['payment_method_id'];
            }, $options);
            
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error loading payment methods: ' . $e->getMessage(), 
                ['user_id' => $user->id, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
        }
        
        // u041eu0431u0440u0430u0431u043eu0442u043au0430 POST-u0437u0430u043fu0440u043eu0441u0430 u0434u043lu044f u043eu0431u043du043eu0432u043bu0435u043du0438u044f u043fu0440u043eu0444u0438u043bu044h
        if ($this->request->isPost()) {
            $data = $this->request->getBody();
            
            // u0412u0430u043bu0438u0434u0430u0446u0438u044f u0434u0430u043du043du044bu0443
            $errors = [];
            
            // u041fu0440u043eu0432u0435u0440u043au0430 u043du0430 u043du0430u043bu0438u0447u0438u0435 u043eu0431u044fu0437u0430u0442u0435u043bu044cu043du044bu0443 u043fu0440u043eu0444u0438u043bu044h
            if (empty($data['name'])) {
                $errors[] = 'Shop name is required';
            }
            
            if (empty($data['description'])) {
                $errors[] = 'Shop description is required';
            }
            
            if (empty($errors)) {
                try {
                    $db = Application::$app->db;
                    $db->beginTransaction();
                    
                    // u041eu0431u043du0435u0432u043bu0435u043du0438u0435 u043eu0441u043du043eu0432u043du044bu0443 u0434u0430u043du043du044bu0443 u043fu0440u043eu0444u0438u043bu044h
                    $updateStmt = $db->prepare("
                        UPDATE seller_profiles 
                        SET name = :name, 
                            description = :description,
                            email = :email,
                            phone = :phone
                        WHERE user_id = :user_id
                    ");
                    
                    $result = $updateStmt->execute([
                        'user_id' => $user->id,
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'email' => $data['email'] ?? '',
                        'phone' => $data['phone'] ?? ''
                    ]);
                    
                    // u041eu0431u0440u0430u0431u043eu0442u043au0430 u0437u0430u0433u0440u0443u0437u043au0438 u0430u0432u0430u0442u0430u0440u0430
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $allowedTypes = ['image/avif', 'image/webp'];
                        $maxSize = 100 * 1024; // 100KB
                        
                        if (in_array($_FILES['avatar']['type'], $allowedTypes) && $_FILES['avatar']['size'] <= $maxSize) {
                            $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                            
                            // u0421u043eu0437u0434u0430u0435u043c u0434u0438u0440u0435u043au0442u043eu0440u0438u044e, u0435u0441u043bu0438 u043eu043du0430 u043du0435 u0441u0443u0447u0435u0441u0442u0432u0443u0435u0442
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            
                            $fileName = $user->id . '_' . time() . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                            $filePath = $uploadDir . $fileName;
                            
                            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
                                // u041eu0431u043du0435u0432u043bu044fu0435u043c u043fu0443u0442u044c u043a u0430u0432u0430u0442u0430u0440u0443 u0432 u0431u0430u0437u0435 u0434u0430u043du043du044bu0443
                                $avatarUpdateStmt = $db->prepare("UPDATE seller_profiles SET avatar_url = :avatar_url WHERE user_id = :user_id");
                                $avatarUpdateStmt->execute([
                                    'user_id' => $user->id,
                                    'avatar_url' => '/uploads/avatars/' . $fileName
                                ]);
                            } else {
                                $errors[] = 'Failed to upload avatar';
                            }
                        } else {
                            $errors[] = 'Invalid avatar file. Only AVIF and WebP formats are allowed, max size 100KB';
                        }
                    }
                    
                    // u041eu0431u0440u0430u0431u043eu0442u043au0430 u0441u043fu043eu0441u043eu0431u044b u043eu043fu043bu0430u0442u044b
                    if (isset($data['payment_methods']) && is_array($data['payment_methods'])) {
                        // u0423u0434u0430u043bu044fu0435u043c u0442u0435u043au0443u0447u0438u0435 u0441u043fu043eu0441u043eu0431u044b u043eu043fu043bu0430u0442u044b
                        $deletePaymentStmt = $db->prepare("DELETE FROM seller_payment_options WHERE seller_profile_id = :seller_profile_id");
                        $deletePaymentStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
                        
                        // u0414u043eu0431u0430u0432u043bu044fu0435u043c u043du0435u0432u044b u0441u043fu043eu0441u043eu0431u044b u043eu043fu043bu0430u0442u044b
                        foreach ($data['payment_methods'] as $methodId) {
                            $insertPaymentStmt = $db->prepare("
                                INSERT INTO seller_payment_options (seller_profile_id, payment_method_id)
                                VALUES (:seller_profile_id, :payment_method_id)
                            ");
                            $insertPaymentStmt->execute([
                                'seller_profile_id' => $sellerProfile['id'],
                                'payment_method_id' => (int)$methodId
                            ]);
                        }
                    }
                    
                    $db->commit();
                    
                    if ($result) {
                        Application::$app->session->setFlash('success', 'Profile updated successfully');
                        Application::$app->logger->info(
                            'Seller profile updated', 
                            ['user_id' => $user->id],
                            'users.log'
                        );
                    } else {
                        Application::$app->session->setFlash('error', 'Failed to update profile');
                        Application::$app->logger->error(
                            'Failed to update seller profile', 
                            ['user_id' => $user->id],
                            'errors.log'
                        );
                    }
                    
                    // u041fu0435u0440u0435u0437u0430u0433u0440u0443u0436u0430u0435u043c u043fu0440u043eu0444u0438u043bu044c u043fu0440u043eu0441u043bu0435 u043eu0431u043du043eu0432u043bu0435u043du0438u044f
                    $sellerProfile = $this->getSellerProfile($user->id);
                    
                    // u041fu0435u0440u0435u0437u0430u0433u0440u0443u0436u0430u0435u043c u0441u043fu043eu0441u043eu0431u044b u043eu043fu043bu0430u0442u044b
                    $optionsStmt = $db->prepare("SELECT payment_method_id FROM seller_payment_options WHERE seller_profile_id = :seller_profile_id");
                    $optionsStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
                    $options = $optionsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $sellerPaymentOptions = array_map(function($option) {
                        return $option['payment_method_id'];
                    }, $options);
                    
                } catch (\Exception $e) {
                    $db->rollBack();
                    Application::$app->session->setFlash('error', 'Error updating profile: ' . $e->getMessage());
                    Application::$app->logger->error(
                        'Error updating seller profile: ' . $e->getMessage(), 
                        ['user_id' => $user->id, 'trace' => $e->getTraceAsString()],
                        'errors.log'
                    );
                }
            } else {
                Application::$app->session->setFlash('error', implode('<br>', $errors));
            }
        }
        
        $this->view->title = 'Seller Profile';
        return $this->render('seller/profile/index', [
            'user' => $user,
            'sellerProfile' => $sellerProfile,
            'paymentMethods' => $paymentMethods,
            'sellerPaymentOptions' => $sellerPaymentOptions
        ]);
    }
    
    public function addProduct(): string {
        $user = $this->getUserProfile();
        
        if (!$user) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page');
            return Application::$app->response->redirect('/login');
        }
        
        // Get seller profile
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->logger->warning(
                'Attempt to add product without seller profile', 
                ['user_id' => $user->id],
                'products.log'
            );
            Application::$app->session->setFlash('error', 'Для добавления товаров необходимо иметь профиль продавца');
            return Application::$app->response->redirect('/seller');
        }

        // Проверка метода запроса
        if (!$this->request->isPost()) {
            return $this->response->redirect('/seller/products/new');
        }

        // Получение данных из формы
        $body = $this->request->getBody();
        $files = $_FILES; // Получаем файлы из глобальной переменной $_FILES

        // Валидация данных
        $errors = [];

        // Проверка обязательных полей
        $requiredFields = ['product_name', 'description', 'price', 'category_id'];
        foreach ($requiredFields as $field) {
            if (empty($body[$field])) {
                $errors[$field] = 'Это поле обязательно для заполнения';
            }
        }

        // Проверка цены
        if (isset($body['price']) && (!is_numeric($body['price']) || floatval($body['price']) <= 0)) {
            $errors['price'] = 'Цена должна быть положительным числом';
        }

        // Проверка изображения
        if (empty($files['image']) || $files['image']['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = 'Необходимо загрузить изображение товара';
        } else {
            // Проверка типа файла
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
            if (!in_array($files['image']['type'], $allowedTypes)) {
                $errors['image'] = 'Допустимые форматы изображений: JPEG, PNG, GIF, WEBP, AVIF';
            }

            // Проверка размера файла (не более 2MB)
            if ($files['image']['size'] > 2 * 1024 * 1024) {
                $errors['image'] = 'Размер изображения не должен превышать 2MB';
            }
        }

        // Если есть ошибки, возвращаемся на форму
        if (!empty($errors)) {
            // Преобразуем массив ошибок в строку для корректной работы с setFlash
            $errorMessages = '';
            foreach ($errors as $field => $message) {
                $errorMessages .= $field . ': ' . $message . '<br>';
            }
            
            Application::$app->session->setFlash('errors', $errorMessages);
            Application::$app->session->setFlash('old', json_encode($body));
            Application::$app->logger->warning(
                'Product validation failed', 
                ['user_id' => $user->id, 'errors' => $errors],
                'products.log'
            );
            return Application::$app->response->redirect('/seller/products/new');
        }

        try {
            // Загрузка изображения
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid('product_') . '_' . time() . '.' . pathinfo($files['image']['name'], PATHINFO_EXTENSION);
            $uploadPath = $uploadDir . $fileName;

            if (!move_uploaded_file($files['image']['tmp_name'], $uploadPath)) {
                throw new \Exception('Ошибка при загрузке изображения');
            }

            // Определение статуса доступности товара
            $isAvailable = 1; // По умолчанию товар доступен
            if (isset($body['product_status'])) {
                if ($body['product_status'] === 'draft' || $body['product_status'] === 'sold_out') {
                    $isAvailable = 0;
                }
            }

            // Сохранение товара в базу данных
            $db = Application::$app->db;
            $statement = $db->prepare("
                INSERT INTO products (
                    product_name, description, price, seller_profile_id,
                    is_active, available_for_preorder, quantity, weight, created_at, updated_at
                ) VALUES (
                    :product_name, :description, :price, :seller_profile_id,
                    :is_active, :available_for_preorder, :quantity, :weight, NOW(), NOW()
                )
            ");

            $result = $statement->execute([
                'product_name' => $body['product_name'],
                'description' => $body['description'],
                'price' => floatval($body['price']),
                'seller_profile_id' => $sellerProfile['id'],
                'is_active' => $isAvailable,
                'available_for_preorder' => isset($body['available_for_preorder']) ? 1 : 0,
                'quantity' => isset($body['quantity']) ? intval($body['quantity']) : 1,
                'weight' => isset($body['weight']) ? intval($body['weight']) : 0
            ]);

            if ($result) {
                $productId = $db->lastInsertId();
                
                // Сохранение изображения в таблице product_images
                $imageStatement = $db->prepare("
                    INSERT INTO product_images (product_id, image_url, is_main)
                    VALUES (:product_id, :image_url, :is_main)
                ");
                
                $imageResult = $imageStatement->execute([
                    'product_id' => $productId,
                    'image_url' => '/uploads/products/' . $fileName,
                    'is_main' => 1 // Первое изображение устанавливаем как основное
                ]);
                
                if (!$imageResult) {
                    throw new \Exception('Ошибка при сохранении изображения');
                }
                
                Application::$app->logger->info(
                    'New product added', 
                    ['user_id' => $user->id, 'product_id' => $productId, 'product_name' => $body['product_name']],
                    'products.log'
                );

                Application::$app->session->setFlash('success', 'Товар успешно добавлен');
                return Application::$app->response->redirect('/seller/products');
            } else {
                throw new \Exception('Ошибка при сохранении товара');
            }
        } catch (\Exception $e) {
            // Удаляем загруженное изображение в случае ошибки
            if (isset($uploadPath) && file_exists($uploadPath)) {
                unlink($uploadPath);
            }

            Application::$app->logger->error(
                'Error adding product: ' . $e->getMessage(), 
                ['user_id' => $user->id, 'error' => $e->getMessage()],
                'errors.log'
            );

            Application::$app->session->setFlash('error', 'Произошла ошибка при добавлении товара: ' . $e->getMessage());
            Application::$app->session->setFlash('old', json_encode($body));
            return Application::$app->response->redirect('/seller/products/new');
        }
    }

    /**
     * u041fu043eu043bu0443u0448u0435u043du0438u0435 u0441u043fu0438u0441u043au0430 u0432u0441u0435u0445 u043au0430u0442u0435u0433u043eu0440u0438u0439
     * @return array u0421u043fu0438u0441u043eu043a u043au0430u0442u0435u0433u043eu0440u0438u0439
     */
    protected function getCategories(): array {
        try {
            $sql = "SELECT id, name, description FROM categories ORDER BY name ASC";
            $statement = Application::$app->db->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // u0417u0430u043fu0440u0438u0441u044c u043eu0448u0438u0431u043au0438 u0432 u043bu043eu0433
            Application::$app->logger->error(
                'u041eu0448u0438u0431u043au0430 u043fu0440u0438 u043fu043eu043bu0443u0447u0435u043du0438u0438 u043au0430u0442u0435u0433u043eu0440u0438u0439: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }

    /**
     * u041eu0431u043du043e0432u043bu0435u043du0438u0435 u043fu0440u043e0434u0443u043a0442u0430
     * 
     * @return string
     */
    public function updateProduct() {
        if (!$this->request->isPost()) {
            return $this->response->redirect('/seller/products');
        }
        
        try {
            $user = $this->getUserProfile();
            if (!$user) {
                Application::$app->session->setFlash('error', 'u0412u044b u0434u043eu043bu0436u043du044b u0431u044bu0442u044c u0430u0432u0442u043eu0440u0438u0437u043eu0432u0430u043du044b');
                return $this->response->redirect('/login');
            }
            
            $sellerProfile = $this->getSellerProfile($user->id);
            if (!$sellerProfile) {
                Application::$app->session->setFlash('error', 'u0423 u0432u0430u0441 u043du0435u0442 u043fu0440u043eu0444u0438u043bu044f u043fu0440u043eu0434u0430u0432u0446u0430');
                return $this->response->redirect('/seller/profile');
            }
            
            $body = $this->request->getBody();
            
            // u041fu0440u043e0432u0435u0440u043au0430 u043du0430 u043du0430u043b0438u0447u0438u0435 u043e0431u044fu0437u0430u0442u0435u043b0435u0439
            if (empty($body['id']) || empty($body['product_name']) || empty($body['price'])) {
                Application::$app->session->setFlash('error', 'u041du0435u043e0431u0445043e04340438u043c043e u04370430043f043e043b043d0438u0442u044c u0432u04410435 u043e0431u044fu0437u0430u04420435u043b0435u0439');
                return $this->response->redirect('/seller/products');
            }
            
            // u041fu0440u043e0432u0435u0440u043au0430, u0447u0442u043e u043f0440u043e0434u0443u043a0442 u043f04400438u043d04300434043b043504360438u0442 u043d0430 u0442043e043c u043f0440u043e04340430u04320446u0430
            $db = Application::$app->db;
            $checkStatement = $db->prepare("
                SELECT p.* FROM products p
                WHERE p.id = :id AND p.seller_profile_id = :seller_profile_id
            ");
            
            $checkStatement->execute([
                'id' => $body['id'],
                'seller_profile_id' => $sellerProfile['id']
            ]);
            
            $product = $checkStatement->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                Application::$app->session->setFlash('error', 'u041f0440u043e0434u0443u043a0442 u043d0435 u043d0430u043904340435u043d u0438u043b0438 u0443 u04320430u0441 u043d0435u0442 u043f04400430u0432 u043d0430 u0435u0433u043e u0440043504340430u043a04420438u0440u043e04320430u043d0438u0435');
                return $this->response->redirect('/seller/products');
            }
            
            // u041e0431u043d043e0432u043b0435u043d0438u0435 u044104420430u0442u0443u04410430 u043f0440u043e0434u0443u043a0442u0430
            $isActive = 0;
            $isPreorder = 0;
            
            if (isset($body['product_status'])) {
                switch ($body['product_status']) {
                    case 'active':
                        $isActive = 1;
                        $isPreorder = 0;
                        break;
                    case 'inactive':
                        $isActive = 0;
                        $isPreorder = 0;
                        break;
                    case 'preorder':
                        $isActive = 1;
                        $isPreorder = 1;
                        break;
                }
            }
            
            // u041e0431u04400430u0431043e0434u043a0430 u0437043004330430270435u043d0438u0435 u04380437043e0431u0440043004360435043d0438u044f, u0435u0441u043b0438 u043e043d043e u0431044b043b043e u0437043004330430270435u043d0438u0435
            $fileName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/avif', 'image/webp'];
                $maxSize = 100 * 1024; // 100KB
                
                if (in_array($_FILES['image']['type'], $allowedTypes) && $_FILES['image']['size'] <= $maxSize) {
                    $uploadDir = __DIR__ . '/../../public/uploads/products/';
                    
                    // u0421u043e043704340430u0435u043c u04340438u04400435u043a043844043e0440u0438u044e, u0435u0441u043b0438 u043e043d0430 u043d0435 u0441u0443u04470435u0441u044204320443u0435u0442
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = uniqid('product_') . '_' . time() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        // u041e0431u043d043e0432u043b0435u043d0438u0435 u04380437043e0431u0440043004360435u043d0438u044f u0432 u044204300431u043b0438u04460435 product_images
                        $imageStatement = $db->prepare("
                            SELECT id FROM product_images WHERE product_id = :product_id AND is_main = 1
                        ");
                        $imageStatement->execute(['product_id' => $body['id']]);
                        $mainImage = $imageStatement->fetch(PDO::FETCH_ASSOC);
                        
                        if ($mainImage) {
                            // u041e0431u043d043e0432u043b0435u043d0438u0435 u0441u0443u04490435u0441u044204320443u0435u04490435u0435 u04380437043e0431u0440043004360435u043d0438u0435
                            $updateImageStatement = $db->prepare("
                                UPDATE product_images SET image_url = :image_url WHERE id = :id
                            ");
                            $updateImageStatement->execute([
                                'id' => $mainImage['id'],
                                'image_url' => '/uploads/products/' . $fileName
                            ]);
                        } else {
                            // u0421u043e043704340430u043d0438u0435 u043d043e0432043e0433043e u04380437043e0431u0440043004360435u043d0438u044f
                            $insertImageStatement = $db->prepare("
                                INSERT INTO product_images (product_id, image_url, is_main)
                                VALUES (:product_id, :image_url, :is_main)
                            ");
                            $insertImageStatement->execute([
                                'product_id' => $body['id'],
                                'image_url' => '/uploads/products/' . $fileName,
                                'is_main' => 1
                            ]);
                        }
                    } else {
                        throw new \Exception('u041e0430440438u0438u0431043a0430 u043f04400438 u0437043004330430270435u043d0438u0438 u04380437043e0431u0440043004360435u043d0438u044f');
                    }
                } else {
                    throw new \Exception('u041du04350434043e043f043004330430u04410438u043c0438 u04420438u043f u0438u043b0438 u044004300437u043c0435u0440 u04380437043e0431u0440043004360435u043d0438u044f');
                }
            }
            
            // u041e0431u043d043e0432u043b0435u043d0438u0435 u04340430u043d043d0438u0445 u043f0440u043e0434u0443u043a0442u0430
            $updateStatement = $db->prepare("
                UPDATE products SET
                    product_name = :product_name,
                    description = :description,
                    price = :price,
                    is_active = :is_active,
                    available_for_preorder = :available_for_preorder,
                    quantity = :quantity,
                    weight = :weight,
                    updated_at = NOW()
                WHERE id = :id AND seller_profile_id = :seller_profile_id
            ");
            
            $result = $updateStatement->execute([
                'id' => $body['id'],
                'product_name' => $body['product_name'],
                'description' => $body['description'],
                'price' => floatval($body['price']),
                'is_active' => $isActive,
                'available_for_preorder' => $isPreorder,
                'quantity' => isset($body['quantity']) ? intval($body['quantity']) : 1,
                'weight' => isset($body['weight']) ? intval($body['weight']) : 0,
                'seller_profile_id' => $sellerProfile['id']
            ]);
            
            if ($result) {
                Application::$app->logger->info(
                    'Product updated', 
                    ['user_id' => $user->id, 'product_id' => $body['id'], 'product_name' => $body['product_name']],
                    'products.log'
                );
                
                Application::$app->session->setFlash('success', 'u041f0440u043e0434u0443u043a0442 u0443u0441u043f0435u0448u043du043e u043e0431u043d043e0432u043b0435u043d');
            } else {
                throw new \Exception('u041e0430440438u0438u0431043a0430 u043f04400438 u043e0431u043d043e0432u043b0435u043d0438u0438 u043f0440u043e0434u0443u043a0442u0430');
            }
            
            return $this->response->redirect('/seller/products');
            
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error updating product: ' . $e->getMessage(),
                ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]
            );
            
            Application::$app->session->setFlash('error', 'u041e0430440438u0438u0431043a0430 u043f04400438 u043e0431u043d043e0432u043b0435u043d0438u0438 u043f0440u043e0434u0443u043a0442u0430: ' . $e->getMessage());
            return $this->response->redirect('/seller/products');
        }
    }
}
