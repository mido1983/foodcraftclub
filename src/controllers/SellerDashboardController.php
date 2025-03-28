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
            
            return $this->render('seller/products/index', [
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
                    'active_products' => 0,
                    'total_orders' => 0,
                    'total_revenue' => 0,
                    'avg_rating' => 0
                ];
            }
            
            $productsStmt = Application::$app->db->prepare("SELECT COUNT(*) as total FROM products WHERE seller_profile_id = :seller_profile_id");
            $productsStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $totalProducts = $productsStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get count of active products (is_active = 1)
            $activeProductsStmt = Application::$app->db->prepare("SELECT COUNT(*) as total FROM products WHERE seller_profile_id = :seller_profile_id AND is_active = 1");
            $activeProductsStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $activeProducts = $activeProductsStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
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
                'active_products' => (int)$activeProducts,
                'total_orders' => (int)($orderData['order_count'] ?? 0),
                'total_revenue' => (float)($orderData['total_sales'] ?? 0),
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
                'active_products' => 0,
                'total_orders' => 0,
                'total_revenue' => 0,
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
            
            // Improved query - use full_name from users table instead of first_name/last_name
            $statement = Application::$app->db->prepare("
                SELECT o.*, u.email as customer_email, u.full_name as buyer_name
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
            'categories' => $this->getCategories(),
            'ingredients' => $this->getBaseIngredients()
        ]);
    }
    
    /**
     * Обновление и редактирование продукта
     * @return string|Response
     */
    public function updateProduct() {
        if (!$this->request->isPost()) {
            return Application::$app->response->redirect('/seller/products');
        }
        
        try {
            $user = $this->getUserProfile();
            if (!$user) {
                Application::$app->session->setFlash('error', 'Вы должны быть авторизованы');
                return Application::$app->response->redirect('/login');
            }
            
            $sellerProfile = $this->getSellerProfile($user->id);
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Attempt to access new product page without seller profile', 
                    ['user_id' => $user->id]
                );
                Application::$app->session->setFlash('error', 'У вас нет профиля продавца');
                return Application::$app->response->redirect('/seller/profile');
            }
            
            $body = $this->request->getBody();
            
            // Проверка на наличие обязательных полей
            if (empty($body['id']) || empty($body['product_name']) || empty($body['price'])) {
                Application::$app->session->setFlash('error', 'Необходимо заполнить все обязательные поля');
                return Application::$app->response->redirect('/seller/products');
            }
            
            // Проверка наличия изображения
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/avif', 'image/webp'];
                $maxSize = 100 * 1024; // 100KB
                
                if (in_array($_FILES['image']['type'], $allowedTypes) && $_FILES['image']['size'] <= $maxSize) {
                    $uploadDir = __DIR__ . '/../../public/uploads/products/';
                    
                    // Создание директории, если она не существует
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = uniqid('product_') . '_' . time() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        // Обновление изображения в базе данных
                        $imageStatement = Application::$app->db->prepare("
                            SELECT id FROM product_images WHERE product_id = :product_id AND is_main = 1
                        ");
                        $imageStatement->execute(['product_id' => $body['id']]);
                        $mainImage = $imageStatement->fetch(PDO::FETCH_ASSOC);
                        
                        if ($mainImage) {
                            // Обновление существующего изображения
                            $updateImageStatement = Application::$app->db->prepare("
                                UPDATE product_images SET image_url = :image_url WHERE id = :id
                            ");
                            $updateImageStatement->execute([
                                'id' => $mainImage['id'],
                                'image_url' => '/uploads/products/' . $fileName
                            ]);
                        } else {
                            // Добавление нового изображения
                            $insertImageStatement = Application::$app->db->prepare("
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
                        throw new \Exception('Ошибка при загрузке изображения');
                    }
                }
            }
            
            // Обновление данных продукта
            $updateStatement = Application::$app->db->prepare("
                UPDATE products SET
                    product_name = :product_name,
                    description = :description,
                    price = :price,
                    is_active = :is_active,
                    available_for_preorder = :available_for_preorder,
                    quantity = :quantity,
                    weight = :weight,
                    category_id = :category_id
                WHERE id = :id AND seller_profile_id = :seller_profile_id
            ");
            
            $result = $updateStatement->execute([
                'id' => $body['id'],
                'product_name' => $body['product_name'],
                'description' => $body['description'],
                'price' => floatval($body['price']),
                'is_active' => $body['product_status'] === 'active' ? 1 : 0,
                'available_for_preorder' => $body['product_status'] === 'preorder' ? 1 : 0,
                'quantity' => isset($body['quantity']) ? intval($body['quantity']) : 1,
                'weight' => isset($body['weight']) ? intval($body['weight']) : 0,
                'category_id' => !empty($body['category_id']) ? intval($body['category_id']) : null,
                'seller_profile_id' => $sellerProfile['id']
            ]);
            
            if ($result) {
                Application::$app->logger->info(
                    'Product updated', 
                    ['user_id' => $user->id, 'product_id' => $body['id'], 'product_name' => $body['product_name']],
                    'products.log'
                );
                
                // Обработка методов оплаты
                if (isset($body['payment_methods']) && is_array($body['payment_methods'])) {
                    Application::$app->logger->info(
                        'Attempting to add payment methods', 
                        ['user_id' => $user->id, 'seller_profile_id' => $sellerProfile['id'], 'methods' => $body['payment_methods']],
                        'users.log'
                    );
                    
                    // Проверка структуры таблицы
                    try {
                        $tableStmt = Application::$app->db->prepare("SHOW TABLES LIKE 'seller_payment_options'");
                        $tableStmt->execute();
                        if (!$tableStmt->fetch()) {
                            // Таблица не существует, создаем её
                            $createTableStmt = Application::$app->db->prepare("
                                CREATE TABLE IF NOT EXISTS seller_payment_options (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    seller_profile_id INT NOT NULL,
                                    payment_method_id INT NOT NULL,
                                    enabled TINYINT(1) NOT NULL DEFAULT 1,
                                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    UNIQUE KEY unique_seller_payment (seller_profile_id, payment_method_id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                            ");
                            $createTableStmt->execute();
                            Application::$app->logger->info('Created seller_payment_options table', [], 'users.log');
                        }
                    } catch (\Exception $e) {
                        Application::$app->logger->error(
                            'Error checking/creating table', 
                            ['error' => $e->getMessage()],
                            'errors.log'
                        );
                    }
                    
                    // Удаляем текущие методы оплаты
                    try {
                        $deletePaymentStmt = Application::$app->db->prepare("DELETE FROM seller_payment_options WHERE seller_profile_id = :seller_profile_id");
                        $deletePaymentStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
                        Application::$app->logger->info(
                            'Deleted existing payment methods', 
                            ['seller_profile_id' => $sellerProfile['id']],
                            'users.log'
                        );
                    } catch (\Exception $e) {
                        Application::$app->logger->error(
                            'Error deleting payment methods', 
                            ['error' => $e->getMessage()],
                            'errors.log'
                        );
                    }
                    
                    // Добавление новых методов оплаты
                    foreach ($body['payment_methods'] as $methodId) {
                        // Проверка валидности ID метода оплаты
                        if (!is_numeric($methodId) || (int)$methodId <= 0) {
                            Application::$app->logger->error(
                                'Invalid payment method ID', 
                                ['user_id' => $user->id, 'method_id' => $methodId],
                                'errors.log'
                            );
                            continue;
                        }
                        
                        // Проверяем существование метода оплаты
                        $checkMethodStmt = Application::$app->db->prepare("SELECT id FROM payment_methods WHERE id = :id");
                        $checkMethodStmt->execute(['id' => (int)$methodId]);
                        $method = $checkMethodStmt->fetch();
                        
                        if (!$method) {
                            Application::$app->logger->error(
                                'Payment method does not exist', 
                                ['user_id' => $user->id, 'method_id' => $methodId],
                                'errors.log'
                            );
                            continue;
                        }
                        
                        // Логируем попытку добавления
                        Application::$app->logger->info(
                            'Attempting to add payment method', 
                            ['user_id' => $user->id, 'seller_profile_id' => $sellerProfile['id'], 'method_id' => $methodId],
                            'users.log'
                        );
                        
                        try {
                            // Используем максимально простой запрос
                            $sql = "INSERT INTO seller_payment_options (seller_profile_id, payment_method_id, enabled) VALUES (?, ?, ?)"; 
                            $insertPaymentStmt = Application::$app->db->prepare($sql);
                            $insertResult = $insertPaymentStmt->execute([(int)$sellerProfile['id'], (int)$methodId, 1]);
                            
                            // Логируем результат
                            if (!$insertResult) {
                                $errorInfo = $insertPaymentStmt->errorInfo();
                                Application::$app->logger->error(
                                    'Failed to add payment method', 
                                    ['user_id' => $user->id, 'error' => $errorInfo],
                                    'errors.log'
                                );
                            } else {
                                Application::$app->logger->info(
                                    'Payment method added successfully', 
                                    ['user_id' => $user->id, 'method_id' => $methodId],
                                    'users.log'
                                );
                            }
                        } catch (\Exception $e) {
                            Application::$app->logger->error(
                                'Exception adding payment method', 
                                ['user_id' => $user->id, 'error' => $e->getMessage()],
                                'errors.log'
                            );
                        }
                    }
                    
                    // Логируем выбранные способы оплаты
                    Application::$app->logger->info(
                        'Payment methods selected', 
                        ['user_id' => $user->id, 'seller_profile_id' => $sellerProfile['id'], 'methods' => $body['payment_methods']],
                        'users.log'
                    );
                } else {
                    // Если не выбрано ни одного способа оплаты, удаляем все существующие
                    try {
                        $deletePaymentStmt = Application::$app->db->prepare("DELETE FROM seller_payment_options WHERE seller_profile_id = :seller_profile_id");
                        $deletePaymentStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
                        
                        // Логируем удаление всех способов оплаты
                        Application::$app->logger->info(
                            'All payment methods removed', 
                            ['user_id' => $user->id, 'seller_profile_id' => $sellerProfile['id']],
                            'users.log'
                        );
                    } catch (\Exception $e) {
                        Application::$app->logger->error(
                            'Error removing payment methods', 
                            ['error' => $e->getMessage()],
                            'errors.log'
                        );
                    }
                }
                
                // Обработка загрузки аватара
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/avif', 'image/webp'];
                    $maxSize = 100 * 1024; // 100KB
                    
                    if (in_array($_FILES['avatar']['type'], $allowedTypes) && $_FILES['avatar']['size'] <= $maxSize) {
                        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                        
                        // Создаем директорию, если она не существует
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $fileName = $user->id . '_' . time() . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
                            // Обновляем изображение в базе данных
                            try {
                                $avatarUpdateStmt = Application::$app->db->prepare("UPDATE seller_profiles SET avatar_url = :avatar_url WHERE user_id = :user_id");
                                $avatarUpdateStmt->execute([
                                    'user_id' => $user->id,
                                    'avatar_url' => '/uploads/avatars/' . $fileName
                                ]);
                                
                                Application::$app->logger->info(
                                    'Avatar updated successfully', 
                                    ['user_id' => $user->id, 'avatar_url' => '/uploads/avatars/' . $fileName],
                                    'users.log'
                                );
                            } catch (\Exception $e) {
                                Application::$app->logger->error(
                                    'Error updating avatar in database', 
                                    ['user_id' => $user->id, 'error' => $e->getMessage()],
                                    'errors.log'
                                );
                                $errors[] = 'Failed to update avatar in database';
                            }
                        } else {
                            Application::$app->logger->error(
                                'Failed to move uploaded avatar', 
                                ['user_id' => $user->id, 'tmp_name' => $_FILES['avatar']['tmp_name'], 'target' => $filePath],
                                'errors.log'
                            );
                            $errors[] = 'Failed to upload avatar';
                        }
                    } else {
                        Application::$app->logger->error(
                            'Invalid avatar file', 
                            ['user_id' => $user->id, 'type' => $_FILES['avatar']['type'], 'size' => $_FILES['avatar']['size']],
                            'errors.log'
                        );
                        $errors[] = 'Invalid avatar file. Only AVIF and WebP formats are allowed, max size 100KB';
                    }
                }
                
                Application::$app->session->setFlash('success', 'Продукт успешно обновлен');
            } else {
                throw new \Exception('Ошибка при обновлении продукта');
            }
            
            return Application::$app->response->redirect('/seller/products');
            
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error updating product: ' . $e->getMessage(),
                ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]
            );
            
            Application::$app->session->setFlash('error', 'Ошибка при обновлении продукта: ' . $e->getMessage());
            return Application::$app->response->redirect('/seller/products');
        }
    }

    public function deliveryAreas() {
        $this->view->title = 'Delivery Areas Management';
        
        try {
            $user = $this->getUserProfile();
            
            if (!$user) {
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            $db = Application::$app->db;
            $citiesStatement = $db->prepare("SELECT * FROM cities");
            $citiesStatement->execute();
            $cities = $citiesStatement->fetchAll(PDO::FETCH_ASSOC);
            
            // Получение списка районов из базы данных
            $districtsStatement = $db->prepare("SELECT * FROM districts");
            $districtsStatement->execute();
            $districts = $districtsStatement->fetchAll(PDO::FETCH_ASSOC);
            
            // Получение зон доставки текущего продавца
            $deliveryAreasStatement = $db->prepare("
                SELECT sda.*, c.city_name, d.district_name 
                FROM seller_delivery_areas sda
                LEFT JOIN cities c ON sda.city_id = c.id
                LEFT JOIN districts d ON sda.district_id = d.id
                WHERE sda.seller_profile_id = :seller_profile_id
            ");
            $deliveryAreasStatement->execute(['seller_profile_id' => $sellerProfile['id']]);
            $deliveryAreas = $deliveryAreasStatement->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->render('seller/delivery-areas/index', [
                'user' => $user,
                'sellerProfile' => $sellerProfile,
                'cities' => $cities,
                'districts' => $districts,
                'deliveryAreas' => $deliveryAreas,
                'notifications' => $this->getNotifications(),
                'unreadNotifications' => $this->getUnreadNotificationsCount()
            ]);
            
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error in deliveryAreas method', 
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'An error occurred while loading delivery areas: ' . $e->getMessage());
            Application::$app->response->redirect('/seller');
            return '';
        }
    }
    
    // Добавление новой зоны доставки
    // @return string
    public function addDeliveryArea() {
        try {
            $user = $this->getUserProfile();
            
            if (!$user) {
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $cityId = $_POST['city_id'] ?? null;
                $districtId = $_POST['district_id'] ?? null;
                $deliveryFee = $_POST['delivery_fee'] ?? 0;
                $minOrderAmount = $_POST['min_order_amount'] ?? 0;
                
                // Проверка наличия обязательных полей
                if (!$cityId || !$districtId) {
                    Application::$app->session->setFlash('error', 'City and district are required fields');
                    Application::$app->response->redirect('/seller/delivery-areas');
                    return '';
                }
                
                // Проверка существования города и района
                $db = Application::$app->db;
                $cityCheck = $db->prepare("SELECT id FROM cities WHERE id = :id");
                $cityCheck->execute(['id' => $cityId]);
                $city = $cityCheck->fetch(PDO::FETCH_ASSOC);
                
                if (!$city) {
                    Application::$app->session->setFlash('error', 'Selected city does not exist');
                    Application::$app->response->redirect('/seller/delivery-areas');
                    return '';
                }
                
                $districtCheck = $db->prepare("SELECT id FROM districts WHERE id = :id");
                $districtCheck->execute(['id' => $districtId]);
                $district = $districtCheck->fetch(PDO::FETCH_ASSOC);
                
                if (!$district) {
                    Application::$app->session->setFlash('error', 'Selected district does not exist');
                    Application::$app->response->redirect('/seller/delivery-areas');
                    return '';
                }
                
                // Проверка существования зоны доставки
                $existingCheck = $db->prepare("
                    SELECT id FROM seller_delivery_areas 
                    WHERE seller_profile_id = :seller_profile_id 
                    AND city_id = :city_id 
                    AND district_id = :district_id
                ");
                
                $existingCheck->execute([
                    'seller_profile_id' => $sellerProfile['id'],
                    'city_id' => $cityId,
                    'district_id' => $districtId
                ]);
                
                $existing = $existingCheck->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    Application::$app->session->setFlash('error', 'This delivery area already exists');
                    Application::$app->response->redirect('/seller/delivery-areas');
                    return '';
                }
                
                // Добавление новой зоны доставки
                $insertStatement = $db->prepare("
                    INSERT INTO seller_delivery_areas (seller_profile_id, city_id, district_id, delivery_fee, free_from_amount)
                    VALUES (:seller_profile_id, :city_id, :district_id, :delivery_fee, :free_from_amount)
                ");
                
                $result = $insertStatement->execute([
                    'seller_profile_id' => $sellerProfile['id'],
                    'city_id' => $cityId,
                    'district_id' => $districtId,
                    'delivery_fee' => $deliveryFee,
                    'free_from_amount' => $minOrderAmount
                ]);
                
                if ($result) {
                    Application::$app->session->setFlash('success', 'Delivery area added successfully');
                } else {
                    Application::$app->session->setFlash('error', 'Failed to add delivery area');
                }
                
                Application::$app->response->redirect('/seller/delivery-areas');
                return '';
            }
            
            Application::$app->response->redirect('/seller/delivery-areas');
            return '';
            
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error in addDeliveryArea method', 
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'An error occurred while adding delivery area: ' . $e->getMessage());
            Application::$app->response->redirect('/seller/delivery-areas');
            return '';
        }
    }
    
    // Удаление зоны доставки
    // @return string
    public function deleteDeliveryArea() {
        try {
            $user = $this->getUserProfile();
            
            if (!$user) {
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            $body = $this->request->getBody();
            
            // Проверка наличия ID зоны доставки
            if (!isset($body['id'])) {
                Application::$app->session->setFlash('error', 'Delivery area ID is required');
                Application::$app->response->redirect('/seller/delivery-areas');
                return '';
            }
            
            $areaId = intval($body['id']);
            
            // Проверка существования зоны доставки
            $db = Application::$app->db;
            $checkStatement = $db->prepare("
                SELECT id FROM seller_delivery_areas 
                WHERE id = :id AND seller_profile_id = :seller_profile_id
            ");
            $checkStatement->execute([
                'id' => $areaId,
                'seller_profile_id' => $sellerProfile['id']
            ]);
            
            if ($checkStatement->rowCount() === 0) {
                Application::$app->session->setFlash('error', 'Delivery area not found or does not belong to you');
                Application::$app->response->redirect('/seller/delivery-areas');
                return '';
            }
            
            // Удаление зоны доставки
            $deleteStatement = $db->prepare("
                DELETE FROM seller_delivery_areas 
                WHERE id = :id AND seller_profile_id = :seller_profile_id
            ");
            
            $result = $deleteStatement->execute([
                'id' => $areaId,
                'seller_profile_id' => $sellerProfile['id']
            ]);
            
            if ($result) {
                Application::$app->logger->info(
                    'Delivery area deleted', 
                    ['user_id' => $user->id, 'area_id' => $areaId],
                    'users.log'
                );
                
                Application::$app->session->setFlash('success', 'Delivery area deleted successfully');
            } else {
                Application::$app->session->setFlash('error', 'Failed to delete delivery area');
            }
            
            Application::$app->response->redirect('/seller/delivery-areas');
            return '';
            
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error in deleteDeliveryArea method', 
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            Application::$app->session->setFlash('error', 'An error occurred while deleting delivery area: ' . $e->getMessage());
            Application::$app->response->redirect('/seller/delivery-areas');
            return '';
        }
    }
    
    /**
     * Обновление и редактирование профиля продавца
     * @return string
     */
    public function profile(): string {
        $user = $this->getUserProfile();
        
        if (!$user) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page');
            return Application::$app->response->redirect('/login');
        }
        
        // Get seller profile
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->logger->warning(
                'Attempt to access seller profile page without seller profile', 
                ['user_id' => $user->id]
            );
            Application::$app->session->setFlash('error', 'You need a seller profile to view this page');
            return Application::$app->response->redirect('/seller');
        }
        
        // Get payment methods
        $paymentMethods = [];
        $sellerPaymentOptions = [];
        
        try {
            $db = Application::$app->db;
            
            // Get all payment methods
            $methodsStmt = $db->prepare("SELECT * FROM payment_methods ORDER BY method_name");
            $methodsStmt->execute();
            $paymentMethods = $methodsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Get seller's payment options
            $optionsStmt = $db->prepare("SELECT payment_method_id FROM seller_payment_options WHERE seller_profile_id = :seller_profile_id");
            $optionsStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
            $options = $optionsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Convert options to array of IDs
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
        
        // Handle POST request
        if ($this->request->isPost()) {
            $data = $this->request->getBody();
            
            // Validate data
            $errors = [];
            
            // Check required fields
            if (empty($data['name'])) {
                $errors[] = 'Shop name is required';
            }
            
            if (empty($data['description'])) {
                $errors[] = 'Shop description is required';
            }
            
            if (!empty($errors)) {
                Application::$app->session->setFlash('error', implode('<br>', $errors));
            } else {
                try {
                    $db = Application::$app->db;
                    $db->beginTransaction();
                    
                    // Update seller profile
                    $updateStmt = $db->prepare("
                        UPDATE seller_profiles 
                        SET name = :name, 
                            description = :description,
                            email = :email,
                            phone = :phone,
                            min_order_amount = :min_order_amount
                        WHERE user_id = :user_id
                    ");
                    
                    $result = $updateStmt->execute([
                        'user_id' => $user->id,
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'email' => $data['email'] ?? '',
                        'phone' => $data['phone'] ?? '',
                        'min_order_amount' => !empty($data['min_order_amount']) ? (float)$data['min_order_amount'] : 50.00
                    ]);
                    
                    // Update payment options
                    if (isset($data['payment_methods']) && is_array($data['payment_methods'])) {
                        // Delete existing payment options
                        $deletePaymentStmt = $db->prepare("DELETE FROM seller_payment_options WHERE seller_profile_id = :seller_profile_id");
                        $deletePaymentStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
                        
                        // Add new payment options
                        foreach ($data['payment_methods'] as $methodId) {
                            // Check if method ID is valid
                            if (!is_numeric($methodId) || (int)$methodId <= 0) {
                                Application::$app->logger->error(
                                    'Invalid payment method ID', 
                                    ['user_id' => $user->id, 'method_id' => $methodId],
                                    'errors.log'
                                );
                                continue;
                            }
                            
                            // Check if payment method exists
                            $checkMethodStmt = $db->prepare("SELECT id FROM payment_methods WHERE id = :id");
                            $checkMethodStmt->execute(['id' => (int)$methodId]);
                            if (!$checkMethodStmt->fetch()) {
                                Application::$app->logger->error(
                                    'Payment method does not exist', 
                                    ['user_id' => $user->id, 'method_id' => $methodId],
                                    'errors.log'
                                );
                                continue;
                            }
                            
                            // Add payment option
                            $insertPaymentStmt = $db->prepare("
                                INSERT INTO seller_payment_options (seller_profile_id, payment_method_id, enabled)
                                VALUES (:seller_profile_id, :payment_method_id, :enabled)
                            ");
                            $insertResult = $insertPaymentStmt->execute([
                                'seller_profile_id' => $sellerProfile['id'],
                                'payment_method_id' => (int)$methodId,
                                'enabled' => 1
                            ]);
                            
                            if (!$insertResult) {
                                $errorInfo = $insertPaymentStmt->errorInfo();
                                Application::$app->logger->error(
                                    'Failed to add payment method', 
                                    ['user_id' => $user->id, 'error' => $errorInfo],
                                    'errors.log'
                                );
                            }
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
                    
                    // Update seller profile and payment options
                    $sellerProfile = $this->getSellerProfile($user->id);
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
    
    public function addProduct(): string|\App\Core\Response {
        $user = $this->getUserProfile();
        
        if (!$user) {
            if ($this->request->isAjax()) {
                return Application::$app->response->json(['success' => false, 'message' => 'Вы должны быть авторизованы'], 401);
            }
            Application::$app->session->setFlash('error', 'Вы должны быть авторизованы');
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
            
            if ($this->request->isAjax()) {
                return Application::$app->response->json(['success' => false, 'message' => 'Для добавления товаров необходимо иметь профиль продавца'], 403);
            }
            
            Application::$app->session->setFlash('error', 'Для добавления товаров необходимо иметь профиль продавца');
            return Application::$app->response->redirect('/seller');
        }
        
        // Проверка метода запроса
        if (!$this->request->isPost()) {
            if ($this->request->isAjax()) {
                return Application::$app->response->json(['success' => false, 'message' => 'Недопустимый метод запроса'], 405);
            }
            return Application::$app->response->redirect('/seller/products/new');
        }

        // Получение данных из формы
        $body = $this->request->getBody();
        $files = $_FILES; // Получаем файлы из глобальной переменной $_FILES

        // Валидация данных
        $errors = [];

        // Проверка обязательных полей
        $requiredFields = ['product_name', 'description', 'price', 'category_id', 'ingredients'];
        foreach ($requiredFields as $field) {
            if (empty($body[$field])) {
                $errors[$field] = 'Это поле обязательно для заполнения';
            }
        }

        // Проверка цены
        if (isset($body['price']) && (!is_numeric($body['price']) || floatval($body['price']) <= 0)) {
            $errors['price'] = 'Цена должна быть положительным числом';
        }
        
        // Проверка количества ингредиентов
        if (!isset($body['ingredients']) || !is_array($body['ingredients']) || count($body['ingredients']) < 2) {
            $errors['ingredients'] = 'Необходимо выбрать минимум 2 ингредиента';
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

        // Если есть ошибки, возвращаемся на форму или отправляем JSON с ошибками
        if (!empty($errors)) {
            Application::$app->logger->warning(
                'Product validation failed', 
                ['user_id' => $user->id, 'errors' => $errors],
                'products.log'
            );
            
            if ($this->request->isAjax()) {
                return Application::$app->response->json([
                    'success' => false, 
                    'message' => 'Ошибка валидации данных',
                    'errors' => $errors
                ], 400);
            }
            
            // Преобразуем массив ошибок в строку для корректной работы с setFlash
            $errorMessages = '';
            foreach ($errors as $field => $message) {
                $errorMessages .= $field . ': ' . $message . '<br>';
            }
            
            Application::$app->session->setFlash('errors', $errorMessages);
            Application::$app->session->setFlash('old', json_encode($body));
            
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
            $productStatus = $body['product_status'] ?? 'inactive';
            $isActive = ($productStatus === 'active' || $productStatus === 'preorder') ? 1 : 0;
            $isPreorder = ($productStatus === 'preorder') ? 1 : 0;
            
            // Сохранение товара в базу данных
            $db = Application::$app->db;
            $statement = $db->prepare("
                INSERT INTO products (
                    product_name, description, price, seller_profile_id,
                    is_active, available_for_preorder, quantity, weight, category_id
                ) VALUES (
                    :product_name, :description, :price, :seller_profile_id,
                    :is_active, :available_for_preorder, :quantity, :weight, :category_id
                )
            ");

            $result = $statement->execute([
                'product_name' => $body['product_name'],
                'description' => $body['description'],
                'price' => floatval($body['price']),
                'seller_profile_id' => $sellerProfile['id'],
                'is_active' => $isActive,
                'available_for_preorder' => $isPreorder,
                'quantity' => isset($body['quantity']) ? intval($body['quantity']) : 1,
                'weight' => isset($body['weight']) ? intval($body['weight']) : 0,
                'category_id' => !empty($body['category_id']) ? intval($body['category_id']) : null
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
                
                // Сохранение выбранных ингредиентов в таблицу product_ingredients
                if (isset($body['ingredients']) && is_array($body['ingredients'])) {
                    foreach ($body['ingredients'] as $ingredientId) {
                        // Get the ingredient name from the base_ingredients table
                        $getIngredientStmt = $db->prepare("SELECT name FROM base_ingredients WHERE id = :id");
                        $getIngredientStmt->execute(['id' => $ingredientId]);
                        $ingredientName = $getIngredientStmt->fetchColumn();
                        
                        if (!$ingredientName) {
                            continue; // Skip if ingredient not found
                        }
                        
                        $ingredientStatement = $db->prepare("
                            INSERT INTO product_ingredients (product_id, ingredient_name, ingredient_percentage)
                            VALUES (:product_id, :ingredient_name, :ingredient_percentage)
                        ");
                        
                        $ingredientResult = $ingredientStatement->execute([
                            'product_id' => $productId,
                            'ingredient_name' => $ingredientName,
                            'ingredient_percentage' => 0.00
                        ]);
                        
                        if (!$ingredientResult) {
                            throw new \Exception('Ошибка при сохранении ингредиентов');
                        }
                    }
                }
                
                Application::$app->logger->info(
                    'New product added', 
                    ['user_id' => $user->id, 'product_id' => $productId, 'product_name' => $body['product_name']],
                    'products.log'
                );

                if ($this->request->isAjax()) {
                    return Application::$app->response->json([
                        'success' => true, 
                        'message' => 'Товар успешно добавлен',
                        'product_id' => $productId
                    ]);
                }
                
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

            if ($this->request->isAjax()) {
                return Application::$app->response->json([
                    'success' => false, 
                    'message' => 'Произошла ошибка при добавлении товара: ' . $e->getMessage()
                ], 500);
            }
            
            Application::$app->session->setFlash('error', 'Произошла ошибка при добавлении товара: ' . $e->getMessage());
            return Application::$app->response->redirect('/seller/products/new');
        }
    }

    /**
     * Display a list of all orders for the seller
     * 
     * @return string|Response
     */
    public function orders() {
        $user = $this->getUserProfile();
        if (!$user) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page');
            return Application::$app->response->redirect('/login');
        }
        
        if (!$user->hasRole('seller')) {
            Application::$app->session->setFlash('error', 'You must be a seller to access this page');
            return Application::$app->response->redirect('/');
        }
        
        $sellerProfile = $this->getSellerProfile($user->id);
        if (!$sellerProfile) {
            Application::$app->session->setFlash('error', 'You don\'t have a seller profile');
            return Application::$app->response->redirect('/seller/profile');
        }
        
        // Get all orders for this seller
        $orders = $this->getAllOrders($user->id);
        
        return $this->render('seller/orders/index', [
            'orders' => $orders,
            'sellerProfile' => $sellerProfile
        ]);
    }
    
    /**
     * Display details for a specific order
     * 
     * @param int $id Order ID
     * @return string|Response
     */
    public function viewOrder(int $id) {
        $user = $this->getUserProfile();
        if (!$user) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page');
            return Application::$app->response->redirect('/login');
        }
        
        if (!$user->hasRole('seller')) {
            Application::$app->session->setFlash('error', 'You must be a seller to access this page');
            return Application::$app->response->redirect('/');
        }
        
        $sellerProfile = $this->getSellerProfile($user->id);
        if (!$sellerProfile) {
            Application::$app->session->setFlash('error', 'You don\'t have a seller profile');
            return Application::$app->response->redirect('/seller/profile');
        }
        
        // Get the order details
        $order = $this->getOrderById($id, $sellerProfile['id']);
        
        if (!$order) {
            Application::$app->session->setFlash('error', 'Order not found or you don\'t have permission to view it');
            return Application::$app->response->redirect('/seller/orders');
        }
        
        // Get the order items
        $orderItems = $this->getOrderItems($id);
        
        return $this->render('seller/orders/view', [
            'order' => $order,
            'orderItems' => $orderItems,
            'sellerProfile' => $sellerProfile
        ]);
    }
    
    /**
     * Update the status of an order
     * 
     * @param int $id Order ID
     * @return Response
     */
    public function updateOrderStatus(int $id) {
        $user = $this->getUserProfile();
        if (!$user) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page');
            return Application::$app->response->redirect('/login');
        }
        
        if (!$user->hasRole('seller')) {
            Application::$app->session->setFlash('error', 'You must be a seller to access this page');
            return Application::$app->response->redirect('/');
        }
        
        $sellerProfile = $this->getSellerProfile($user->id);
        if (!$sellerProfile) {
            Application::$app->session->setFlash('error', 'You don\'t have a seller profile');
            return Application::$app->response->redirect('/seller/profile');
        }
        
        // Check if the order exists and belongs to this seller
        $order = $this->getOrderById($id, $sellerProfile['id']);
        
        if (!$order) {
            Application::$app->session->setFlash('error', 'Order not found or you don\'t have permission to update it');
            return Application::$app->response->redirect('/seller/orders');
        }
        
        // Get the new status from the form
        $body = $this->request->getBody();
        $status = $body['status'] ?? '';
        
        // Validate the status
        $validStatuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            Application::$app->session->setFlash('error', 'Invalid status');
            return Application::$app->response->redirect("/seller/orders/{$id}");
        }
        
        // Update the order status
        try {
            $db = Application::$app->db;
            $statement = $db->prepare("UPDATE orders SET status = :status WHERE id = :id AND seller_profile_id = :seller_profile_id");
            $statement->execute([
                'status' => $status,
                'id' => $id,
                'seller_profile_id' => $sellerProfile['id']
            ]);
            
            Application::$app->session->setFlash('success', 'Order status updated successfully');
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error updating order status: ' . $e->getMessage(),
                ['order_id' => $id, 'seller_id' => $sellerProfile['id'], 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            Application::$app->session->setFlash('error', 'An error occurred while updating the order status');
        }
        
        return Application::$app->response->redirect("/seller/orders/{$id}");
    }
    
    /**
     * Get all orders for a seller
     * 
     * @param int $userId User ID
     * @return array
     */
    protected function getAllOrders(int $userId): array {
        try {
            $sellerProfile = $this->getSellerProfile($userId);
            
            if (!$sellerProfile) {
                return [];
            }
            
            $statement = Application::$app->db->prepare("
                SELECT o.*, u.email as customer_email, u.full_name as buyer_name
                FROM orders o
                JOIN users u ON o.buyer_id = u.id
                WHERE o.seller_profile_id = :seller_profile_id
                ORDER BY o.order_date DESC
            ");
            $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error getting all orders: ' . $e->getMessage(), 
                ['user_id' => $userId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            return [];
        }
    }
    
    /**
     * Get a specific order by ID
     * 
     * @param int $orderId Order ID
     * @param int $sellerProfileId Seller profile ID
     * @return array|null
     */
    protected function getOrderById(int $orderId, int $sellerProfileId): ?array {
        try {
            // First, get the basic order information
            $statement = Application::$app->db->prepare("
                SELECT o.*, u.email as customer_email, u.full_name as buyer_name,
                       pm.method_name as payment_method_name,
                       c.city_name, d.district_name,
                       om.phone
                FROM orders o
                JOIN users u ON o.buyer_id = u.id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                LEFT JOIN cities c ON o.city_id = c.id
                LEFT JOIN districts d ON o.district_id = d.id
                LEFT JOIN order_metadata om ON o.id = om.order_id
                WHERE o.id = :id AND o.seller_profile_id = :seller_profile_id
            ");
            $statement->execute([
                'id' => $orderId,
                'seller_profile_id' => $sellerProfileId
            ]);
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return null;
            }
            
            // If phone is not set, use a default value
            if (!isset($result['phone']) || empty($result['phone'])) {
                $result['phone'] = 'Номер телефона не указан';
            }
            
            return $result;
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error getting order by ID: ' . $e->getMessage(), 
                ['order_id' => $orderId, 'seller_profile_id' => $sellerProfileId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            return null;
        }
    }
    
    /**
     * Get items for a specific order
     * 
     * @param int $orderId Order ID
     * @return array
     */
    protected function getOrderItems(int $orderId): array {
        try {
            $statement = Application::$app->db->prepare("
                SELECT oi.*, p.product_name, p.price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id
            ");
            $statement->execute(['order_id' => $orderId]);
            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Error getting order items: ' . $e->getMessage(), 
                ['order_id' => $orderId, 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            
            return [];
        }
    }


    protected function getBaseIngredients(): array {
        try {
            $sql = "SELECT id, name, category, kosher, allergen FROM base_ingredients ORDER BY category, name";
            $statement = Application::$app->db->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Ошибка при получении ингредиентов: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }


    protected function getCategories(): array {
        try {
            $sql = "SELECT id, name, description FROM categories";
            $statement = Application::$app->db->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // u0417u0430u043fu0440u043eu0441 u043eu0448u0438u0431u043au0438 u0432 u043lu043eu0433
            Application::$app->logger->error(
                'Ошибка при получении категорий: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()],
                'errors.log'
            );
            return [];
        }
    }

    public function deleteProduct($productId) {
        Application::$app->logger->info(
            'Request to delete product', 
            ['product_id' => $productId],
            'products.log'
        );

        try {
            // Получаем информацию о продукте
            $productStmt = Application::$app->db->prepare(
                "SELECT p.*, sp.user_id 
                 FROM products p 
                 JOIN seller_profiles sp ON p.seller_profile_id = sp.id 
                 WHERE p.id = :product_id"
            );
            $productStmt->execute(['product_id' => $productId]);
            $product = $productStmt->fetch();

            if (!$product) {
                Application::$app->logger->warning(
                    'Attempt to delete non-existent product', 
                    ['product_id' => $productId],
                    'errors.log'
                );
                return json_encode(['success' => false, 'message' => 'Продукт не найден']);
            }

            // Проверяем, что пользователь имеет право удалять этот продукт
            $currentUser = $this->getUserProfile();
            if ((int)$currentUser->id !== (int)$product['user_id']) {
                Application::$app->logger->warning(
                    'Unauthorized attempt to delete product', 
                    ['product_id' => $productId, 'user_id' => $currentUser->id],
                    'security.log'
                );
                return json_encode(['success' => false, 'message' => 'Нет прав на удаление']);
            }

            // Удаляем продукт
            $deleteStmt = Application::$app->db->prepare("DELETE FROM products WHERE id = :product_id");
            $deleteStmt->execute(['product_id' => $productId]);

            Application::$app->logger->info(
                'Product deleted successfully', 
                ['product_id' => $productId, 'user_id' => $currentUser->id],
                'products.log'
            );

            return json_encode(['success' => true, 'message' => 'Продукт успешно удален']);

        } catch (Exception $e) {
            Application::$app->logger->error(
                'Error deleting product', 
                ['product_id' => $productId, 'error' => $e->getMessage()],
                'errors.log'
            );
            return json_encode(['success' => false, 'message' => 'Ошибка при удалении продукта: ' . $e->getMessage()]);
        }
    }
}
