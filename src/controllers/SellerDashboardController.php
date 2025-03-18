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
        
        // Добавляем логирование для отслеживания доступа к странице продавца
        Application::$app->logger->info(
            'Запрос на страницу продавца', 
            ['uri' => $_SERVER['REQUEST_URI']],
            'users.log'
        );
        
        try {
            $user = $this->getUserProfile();
            
            // Проверяем, есть ли пользователь
            if (!$user) {
                Application::$app->logger->warning(
                    'Попытка доступа к странице продавца без авторизации', 
                    [],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'Вы должны быть авторизованы для доступа к этой странице');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            // Проверяем роль пользователя
            $roles = $user->getRoles();
            $hasSellerRole = false;
            foreach ($roles as $role) {
                if ($role['name'] === 'seller') {
                    $hasSellerRole = true;
                    break;
                }
            }
            
            if (!$hasSellerRole) {
                Application::$app->logger->warning(
                    'Попытка доступа к странице продавца без роли продавца', 
                    ['user_id' => $user->id, 'roles' => $roles],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'У вас нет прав для доступа к этой странице');
                Application::$app->response->redirect('/');
                return '';
            }
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            // Если профиль продавца не найден, логгируем это
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Профиль продавца не найден при доступе к странице', 
                    ['user_id' => $user->id],
                    'users.log'
                );
            }
            
            // Логгируем успешный доступ к странице продавца
            Application::$app->logger->info(
                'Успешный доступ к странице продавца', 
                ['user_id' => $user->id, 'seller_profile_id' => $sellerProfile ? $sellerProfile['id'] : null],
                'users.log'
            );
            
            return $this->render('seller/dashboard/index', [
                'user' => $user,
                'sellerProfile' => $sellerProfile,
                'stats' => $this->getSellerStats($user->id),
                'recentOrders' => $this->getRecentOrders($user->id),
                'notifications' => $this->getNotifications(),
                'unreadNotifications' => $this->getUnreadNotificationsCount()
            ]);
        } catch (Exception $e) {
            // Логгируем ошибку
            Application::$app->logger->error(
                'Ошибка при доступе к странице продавца: ' . $e->getMessage(), 
                ['exception' => $e->getTraceAsString()],
                'errors.log'
            );
            
            // Устанавливаем сообщение об ошибке
            Application::$app->session->setFlash('error', 'Произошла ошибка при загрузке страницы продавца. Пожалуйста, попробуйте еще раз.');
            
            // Перенаправляем на главную страницу
            Application::$app->response->redirect('/');
            return '';
        }
    }

    /**
     * Страница управления продуктами продавца
     * @return string
     */
    public function products() {
        $this->view->title = 'My Products';
        
        // Добавляем логирование для отслеживания доступа к странице продуктов
        Application::$app->logger->info(
            'Запрос на страницу продуктов', 
            ['uri' => $_SERVER['REQUEST_URI']],
            'users.log'
        );
        
        try {
            $user = $this->getUserProfile();
            
            // Проверяем, есть ли пользователь
            if (!$user) {
                Application::$app->logger->warning(
                    'Попытка доступа к странице продуктов без авторизации', 
                    [],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'You must be logged in to access this page');
                Application::$app->response->redirect('/login');
                return '';
            }
            
            // Логирование информации о пользователе
            Application::$app->logger->info(
                'Пользователь зашел на страницу продуктов', 
                ['user_id' => $user->id, 'roles' => $user->getRoles()],
                'users.log'
            );
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Попытка доступа к странице продуктов без профиля продавца', 
                    ['user_id' => $user->id],
                    'users.log'
                );
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            // Проверяем существование таблицы categories
            $checkCategoriesTable = Application::$app->db->prepare("
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'categories'
            ");
            $checkCategoriesTable->execute();
            $categoriesTableExists = $checkCategoriesTable->fetchColumn();
            
            // Получаем все продукты продавца
            if ($categoriesTableExists) {
                // Если таблица categories существует, используем JOIN
                $statement = Application::$app->db->prepare("
                    SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            } else {
                // Если таблицы categories нет, получаем только продукты
                $statement = Application::$app->db->prepare("
                    SELECT p.*, '' as category_name
                    FROM products p
                    WHERE p.seller_profile_id = :seller_profile_id
                    ORDER BY p.created_at DESC
                ");
            }
            
            $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
            $products = $statement->fetchAll();
            
            // Логирование количества найденных продуктов
            Application::$app->logger->info(
                'Найдено продуктов', 
                ['user_id' => $user->id, 'count' => count($products)],
                'users.log'
            );
            
            // Получаем все категории для формы добавления/редактирования
            $categories = [];
            if ($categoriesTableExists) {
                $categoriesStmt = Application::$app->db->prepare("SELECT id, name FROM categories ORDER BY name");
                $categoriesStmt->execute();
                $categories = $categoriesStmt->fetchAll();
            }
            
            // Логирование перед рендерингом страницы
            Application::$app->logger->info(
                'Рендеринг страницы продуктов', 
                ['user_id' => $user->id, 'template' => 'seller/products/index'],
                'users.log'
            );
            
            return $this->render('seller/products/index', [
                'user' => $user,
                'sellerProfile' => $sellerProfile,
                'products' => $products,
                'categories' => $categories,
                'notifications' => $this->getNotifications(),
                'unreadNotifications' => $this->getUnreadNotificationsCount()
            ]);
        } catch (\Exception $e) {
            // Логгируем ошибку
            Application::$app->logger->error(
                'Ошибка при доступе к странице продуктов: ' . $e->getMessage(), 
                ['exception' => $e->getTraceAsString()],
                'errors.log'
            );
            
            // Устанавливаем сообщение об ошибке
            Application::$app->session->setFlash('error', 'Произошла ошибка при загрузке страницы продуктов. Пожалуйста, попробуйте еще раз.');
            
            // Перенаправляем на главную страницу
            Application::$app->response->redirect('/seller');
            return '';
        }
    }

    /**
     * Обработка добавления нового продукта
     * @return string
     */
    public function addProduct() {
        // Логирование начала процесса добавления продукта
        Application::$app->logger->info(
            'Начало процесса добавления продукта', 
            ['uri' => $_SERVER['REQUEST_URI'], 'method' => $_SERVER['REQUEST_METHOD']],
            'products.log'
        );
        
        if (!Application::$app->request->isPost()) {
            Application::$app->logger->warning(
                'Попытка добавить продукт не через POST метод', 
                ['method' => $_SERVER['REQUEST_METHOD']],
                'errors.log'
            );
            Application::$app->response->redirect('/seller/products');
            return '';
        }
        
        try {
            $user = $this->getUserProfile();
            
            // Логирование информации о пользователе
            Application::$app->logger->info(
                'Пользователь пытается добавить продукт', 
                ['user_id' => $user->id, 'roles' => $user->getRoles()],
                'products.log'
            );
            
            // Получаем профиль продавца для текущего пользователя
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Попытка доступа к странице добавления продукта без профиля продавца', 
                    ['user_id' => $user->id],
                    'errors.log'
                );
                Application::$app->session->setFlash('error', 'Seller profile not found');
                Application::$app->response->redirect('/seller');
                return '';
            }
            
            // Получаем данные из формы
            $data = Application::$app->request->getBody();
            
            Application::$app->logger->info(
                'Получены данные для добавления продукта', 
                ['data' => $data],
                'products.log'
            );
            
            // Используем seller_profile_id из профиля продавца вместо формы
            $sellerProfileId = $sellerProfile['id'];
            
            // Валидация данных
            if (empty($data['product_name']) || empty($data['description']) || !isset($data['price'])) {
                Application::$app->session->setFlash('error', 'Please fill in all required fields');
                Application::$app->logger->warning(
                    'Попытка добавить продукт с пустыми обязательными полями', 
                    ['data' => $data],
                    'products.log'
                );
                Application::$app->response->redirect('/seller/products');
                return '';
            }
            
            // Обработка загруженного изображения
            $imageUrl = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = Application::$app->rootPath . '/public/uploads/products/';
                
                // Создаем директорию, если она не существует
                if (!file_exists($uploadDir)) {
                    Application::$app->logger->info(
                        'Создание директории для загрузки изображений', 
                        ['dir' => $uploadDir],
                        'products.log'
                    );
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = uniqid('product_') . '_' . basename($_FILES['image']['name']);
                $uploadFile = $uploadDir . $fileName;
                
                // Проверяем тип файла
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (!in_array($fileType, $allowedTypes)) {
                    Application::$app->session->setFlash('error', 'Invalid file type. Only JPG, PNG, GIF and WEBP are allowed.');
                    Application::$app->response->redirect('/seller/products');
                    return '';
                }
                
                // Проверяем размер файла (макс. 2MB)
                if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                    Application::$app->session->setFlash('error', 'File is too large. Maximum size is 2MB.');
                    Application::$app->response->redirect('/seller/products');
                    return '';
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                    $imageUrl = '/uploads/products/' . $fileName;
                } else {
                    Application::$app->logger->error(
                        'Failed to upload product image', 
                        ['user_id' => $user->id, 'file' => $_FILES['image']['name']],
                        'products.log'
                    );
                }
            }
            
            // Подготовка данных для вставки
            $isActive = 0;
            $availableForPreorder = 0;
            
            // Обработка статуса продукта
            if (isset($data['product_status'])) {
                switch ($data['product_status']) {
                    case 'active':
                        $isActive = 1;
                        break;
                    case 'draft':
                        $isActive = 0;
                        break;
                    case 'sold_out':
                        $isActive = 0;
                        break;
                    default:
                        $isActive = 1;
                }
            } else {
                // Для совместимости с предыдущей версией
                $isActive = isset($data['is_active']) ? 1 : 0;
            }
            
            // Обработка флага предзаказа
            $availableForPreorder = isset($data['available_for_preorder']) ? 1 : 0;
            
            // Логирование статуса продукта
            Application::$app->logger->info(
                'Статус продукта при добавлении', 
                [
                    'product_status' => $data['product_status'] ?? 'не указан',
                    'is_active' => $isActive,
                    'available_for_preorder' => $availableForPreorder
                ],
                'products.log'
            );
            
            $categoryId = !empty($data['category_id']) ? $data['category_id'] : null;
            
            try {
                $statement = Application::$app->db->prepare("
                    INSERT INTO products (product_name, description, price, category_id, seller_profile_id, is_active, available_for_preorder, created_at, updated_at)
                    VALUES (:name, :description, :price, :category_id, :seller_profile_id, :is_active, :available_for_preorder, NOW(), NOW())
                ");
                
                // Логирование SQL запроса и параметров
                Application::$app->logger->info(
                    'Выполнение SQL запроса на добавление продукта', 
                    [
                        'sql' => "INSERT INTO products (product_name, description, price, category_id, seller_profile_id, is_active, available_for_preorder, created_at, updated_at) VALUES (:name, :description, :price, :category_id, :seller_profile_id, :is_active, :available_for_preorder, NOW(), NOW())",
                        'params' => [
                            'name' => $data['product_name'],
                            'description' => $data['description'],
                            'price' => (float)$data['price'],
                            'category_id' => $categoryId,
                            'seller_profile_id' => $sellerProfileId,
                            'is_active' => $isActive,
                            'available_for_preorder' => $availableForPreorder
                        ]
                    ],
                    'products.log'
                );
                
                $statement->bindValue(':name', $data['product_name']);
                $statement->bindValue(':description', $data['description']);
                $statement->bindValue(':price', (float)$data['price']);
                $statement->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
                $statement->bindValue(':seller_profile_id', $sellerProfileId, PDO::PARAM_INT);
                $statement->bindValue(':is_active', $isActive, PDO::PARAM_INT);
                $statement->bindValue(':available_for_preorder', $availableForPreorder, PDO::PARAM_INT);
                
                $statement->execute();
                
                $productId = Application::$app->db->lastInsertId();
                
                // Если есть изображение, добавляем его в таблицу product_images
                if (!empty($imageUrl)) {
                    $imageStatement = Application::$app->db->prepare("
                        INSERT INTO product_images (product_id, image_url, is_main)
                        VALUES (:product_id, :image_url, 1)
                    ");
                    
                    $imageStatement->bindValue(':product_id', $productId, PDO::PARAM_INT);
                    $imageStatement->bindValue(':image_url', $imageUrl);
                    $imageStatement->execute();
                    
                    Application::$app->logger->info(
                        'Добавлено изображение для продукта', 
                        ['product_id' => $productId, 'image_url' => $imageUrl],
                        'products.log'
                    );
                }
                
                Application::$app->logger->info(
                    'Продукт успешно добавлен', 
                    ['product_id' => $productId],
                    'products.log'
                );
                
                Application::$app->session->setFlash('success', 'Продукт успешно добавлен');
                Application::$app->response->redirect('/seller/products');
                return '';
            } catch (\Exception $e) {
                Application::$app->session->setFlash('error', 'An error occurred: ' . $e->getMessage());
                Application::$app->logger->error(
                    'Exception when adding product: ' . $e->getMessage(), 
                    [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'data' => $data,
                        'sql_error' => Application::$app->db->errorInfo()
                    ],
                    'errors.log'
                );
            }
            
            Application::$app->response->redirect('/seller/products');
            return '';
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Exception when adding product: ' . $e->getMessage(), 
                [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                'errors.log'
            );
            Application::$app->session->setFlash('error', 'An error occurred: ' . $e->getMessage());
            Application::$app->response->redirect('/seller/products');
            return '';
        }
    }

    /**
     * Обработка редактирования продукта
     * @return string
     */
    public function editProduct() {
        if (!$this->request->isPost()) {
            $this->redirect('/seller/products');
            return '';
        }
        
        $user = $this->getUserProfile();
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->session->setFlash('error', 'Seller profile not found');
            $this->redirect('/seller');
            return '';
        }
        
        $data = $this->request->getBody();
        
        // Проверяем, что продукт существует и принадлежит текущему продавцу
        $statement = Application::$app->db->prepare("
            SELECT * FROM products 
            WHERE id = :id AND seller_profile_id = :seller_profile_id
        ");
        $statement->execute([
            'id' => $data['id'],
            'seller_profile_id' => $sellerProfile['id']
        ]);
        $product = $statement->fetch();
        
        if (!$product) {
            Application::$app->logger->warning(
                'Попытка редактировать чужой или несуществующий продукт', 
                ['user_id' => $user->id, 'product_id' => $data['id']],
                'security.log'
            );
            Application::$app->session->setFlash('error', 'Product not found or access denied');
            $this->redirect('/seller/products');
            return '';
        }
        
        // Валидация данных
        if (empty($data['name']) || empty($data['description']) || !isset($data['price'])) {
            Application::$app->session->setFlash('error', 'Please fill in all required fields');
            $this->redirect('/seller/products');
            return '';
        }
        
        // Обработка загруженного изображения
        $imageUrl = $product['image_url']; // По умолчанию оставляем текущее изображение
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = Application::$app->rootPath . '/public/uploads/products/';
            
            // Создаем директорию, если она не существует
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid('product_') . '_' . basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            // Проверяем тип файла
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                Application::$app->session->setFlash('error', 'Invalid file type. Only JPG, PNG, GIF and WEBP are allowed.');
                $this->redirect('/seller/products');
                return '';
            }
            
            // Проверяем размер файла (макс. 2MB)
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                Application::$app->session->setFlash('error', 'File is too large. Maximum size is 2MB.');
                $this->redirect('/seller/products');
                return '';
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $imageUrl = '/uploads/products/' . $fileName;
                
                // Удаляем старое изображение, если оно существует
                if (!empty($product['image_url'])) {
                    $oldImagePath = Application::$app->rootPath . '/public' . $product['image_url'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            } else {
                Application::$app->logger->error(
                    'Failed to upload product image during edit', 
                    ['user_id' => $user->id, 'product_id' => $data['id'], 'file' => $_FILES['image']['name']],
                    'products.log'
                );
            }
        }
        
        // Подготовка данных для обновления
        $isActive = 0;
        $availableForPreorder = 0;
        
        // Обработка статуса продукта
        if (isset($data['product_status'])) {
            switch ($data['product_status']) {
                case 'active':
                    $isActive = 1;
                    $availableForPreorder = 0;
                    break;
                case 'inactive':
                    $isActive = 0;
                    $availableForPreorder = 0;
                    break;
                case 'preorder':
                    $isActive = 0;
                    $availableForPreorder = 1;
                    break;
                default:
                    $isActive = 1;
                    $availableForPreorder = 0;
            }
        } else {
            // Для совместимости с предыдущей версией
            $isActive = isset($data['is_active']) ? 1 : 0;
        }
        
        $categoryId = !empty($data['category_id']) ? $data['category_id'] : null;
        
        try {
            $statement = Application::$app->db->prepare("
                UPDATE products 
                SET product_name = :name, 
                    description = :description, 
                    price = :price, 
                    category_id = :category_id, 
                    is_active = :is_active, 
                    available_for_preorder = :available_for_preorder, 
                    updated_at = NOW()
                WHERE id = :id AND seller_profile_id = :seller_profile_id
            ");
            
            $result = $statement->execute([
                'id' => $data['id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => (float)$data['price'],
                'category_id' => $categoryId,
                'is_active' => $isActive,
                'available_for_preorder' => $availableForPreorder,
                'seller_profile_id' => $sellerProfile['id']
            ]);
            
            if ($result) {
                Application::$app->logger->info(
                    'Product updated successfully', 
                    ['user_id' => $user->id, 'product_id' => $data['id'], 'product_name' => $data['name']],
                    'products.log'
                );
                Application::$app->session->setFlash('success', 'Product updated successfully');
            } else {
                Application::$app->session->setFlash('error', 'Failed to update product');
                Application::$app->logger->error(
                    'Failed to update product', 
                    ['user_id' => $user->id, 'product_id' => $data['id'], 'product_name' => $data['name']],
                    'products.log'
                );
            }
        } catch (\Exception $e) {
            Application::$app->session->setFlash('error', 'An error occurred: ' . $e->getMessage());
            Application::$app->logger->error(
                'Exception when updating product: ' . $e->getMessage(), 
                [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'data' => $data,
                    'sql_error' => Application::$app->db->errorInfo()
                ],
                'errors.log'
            );
        }
        
        $this->redirect('/seller/products');
        return '';
    }

    /**
     * Обработка удаления продукта
     * @return string
     */
    public function deleteProduct() {
        if (!$this->request->isPost()) {
            $this->redirect('/seller/products');
            return '';
        }
        
        $user = $this->getUserProfile();
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->session->setFlash('error', 'Seller profile not found');
            $this->redirect('/seller');
            return '';
        }
        
        $data = $this->request->getBody();
        
        // Проверяем, что продукт существует и принадлежит текущему продавцу
        $statement = Application::$app->db->prepare("
            SELECT * FROM products 
            WHERE id = :id AND seller_profile_id = :seller_profile_id
        ");
        $statement->execute([
            'id' => $data['id'],
            'seller_profile_id' => $sellerProfile['id']
        ]);
        $product = $statement->fetch();
        
        if (!$product) {
            Application::$app->logger->warning(
                'Попытка удалить чужой или несуществующий продукт', 
                ['user_id' => $user->id, 'product_id' => $data['id']],
                'security.log'
            );
            Application::$app->session->setFlash('error', 'Product not found or access denied');
            $this->redirect('/seller/products');
            return '';
        }
        
        try {
            // Начинаем транзакцию
            Application::$app->db->beginTransaction();
            
            // Проверяем, есть ли связанные заказы
            $orderCheckStmt = Application::$app->db->prepare("
                SELECT COUNT(*) FROM order_items 
                WHERE product_id = :product_id
            ");
            $orderCheckStmt->execute(['product_id' => $data['id']]);
            $hasOrders = (int)$orderCheckStmt->fetchColumn() > 0;
            
            if ($hasOrders) {
                // Если есть заказы, помечаем продукт как неактивный вместо удаления
                $deactivateStmt = Application::$app->db->prepare("
                    UPDATE products 
                    SET is_active = 0, is_deleted = 1, updated_at = NOW() 
                    WHERE id = :id AND seller_profile_id = :seller_profile_id
                ");
                $result = $deactivateStmt->execute([
                    'id' => $data['id'],
                    'seller_profile_id' => $sellerProfile['id']
                ]);
                
                if ($result) {
                    Application::$app->logger->info(
                        'Product marked as deleted (has orders)', 
                        ['user_id' => $user->id, 'product_id' => $data['id'], 'product_name' => $product['name']],
                        'products.log'
                    );
                    Application::$app->session->setFlash('success', 'Product has been deactivated and marked as deleted');
                } else {
                    throw new \Exception('Failed to deactivate product');
                }
            } else {
                // Если нет заказов, удаляем продукт полностью
                $deleteStmt = Application::$app->db->prepare("
                    DELETE FROM products 
                    WHERE id = :id AND seller_profile_id = :seller_profile_id
                ");
                $result = $deleteStmt->execute([
                    'id' => $data['id'],
                    'seller_profile_id' => $sellerProfile['id']
                ]);
                
                if ($result) {
                    // Удаляем изображение, если оно существует
                    if (!empty($product['image_url'])) {
                        $imagePath = Application::$app->rootPath . '/public' . $product['image_url'];
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                    
                    Application::$app->logger->info(
                        'Product deleted successfully', 
                        ['user_id' => $user->id, 'product_id' => $data['id'], 'product_name' => $product['product_name']],
                        'products.log'
                    );
                    Application::$app->session->setFlash('success', 'Product deleted successfully');
                } else {
                    throw new \Exception('Failed to delete product');
                }
            }
            
            // Завершаем транзакцию
            Application::$app->db->commit();
            
        } catch (\Exception $e) {
            // Откатываем транзакцию в случае ошибки
            Application::$app->db->rollBack();
            
            Application::$app->session->setFlash('error', 'An error occurred: ' . $e->getMessage());
            Application::$app->logger->error(
                'Exception when deleting product: ' . $e->getMessage(), 
                [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'data' => $data,
                    'sql_error' => Application::$app->db->errorInfo()
                ],
                'errors.log'
            );
        }
        
        $this->redirect('/seller/products');
        return '';
    }

    /**
     * Отображение формы добавления нового продукта
     * 
     * @return string
     */
    public function newProduct() {
        $this->view->title = 'Добавление нового продукта';
        
        // Логирование доступа к странице добавления продукта
        Application::$app->logger->info(
            'Доступа к странице добавления продукта', 
            ['uri' => $_SERVER['REQUEST_URI'], 'method' => $_SERVER['REQUEST_METHOD']],
            'products.log'
        );
        
        try {
            $user = $this->getUserProfile();
            
            // Логирование информации о пользователе
            Application::$app->logger->info(
                'Пользователь открыл страницу добавления продукта', 
                ['user_id' => $user->id, 'roles' => $user->getRoles()],
                'products.log'
            );
            
            $sellerProfile = $this->getSellerProfile($user->id);
            
            if (!$sellerProfile) {
                Application::$app->logger->warning(
                    'Попытка доступа к странице добавления продукта без профиля продавца', 
                    ['user_id' => $user->id],
                    'errors.log'
                );
                return $this->redirect('/seller/dashboard');
            }
            
            // Получаем список категорий для выпадающего списка
            $categories = [];
            try {
                $stmt = Application::$app->db->prepare("SELECT id, name FROM categories ORDER BY name");
                $stmt->execute();
                $categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                // Если таблица категорий не существует, просто продолжаем с пустым массивом
                Application::$app->logger->warning(
                    'Не удалось загрузить категории: ' . $e->getMessage(), 
                    ['exception' => $e->getMessage()],
                    'products.log'
                );
            }
            
            // Отображаем форму добавления продукта
            return $this->render('seller/products/new', [
                'categories' => $categories,
                'sellerProfile' => $sellerProfile
            ]);
            
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Ошибка при отображении формы добавления продукта: ' . $e->getMessage(), 
                ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                'errors.log'
            );
            Application::$app->session->setFlash('error', 'Произошла ошибка при загрузке формы. Пожалуйста, попробуйте позже.');
            return $this->redirect('/seller/products');
        }
    }

    private function getSellerProfile(int $userId) {
        // Логирование запроса на получение профиля продавца
        Application::$app->logger->info(
            'Запрос на получение профиля продавца', 
            ['user_id' => $userId],
            'users.log'
        );
        
        try {
            // Проверяем, есть ли профиль продавца в базе данных
            $statement = Application::$app->db->prepare("
                SELECT * FROM seller_profiles 
                WHERE user_id = :user_id
            ");
            $statement->execute(['user_id' => $userId]);
            $result = $statement->fetch();
            
            // Логирование результата запроса
            if ($result) {
                Application::$app->logger->info(
                    'Профиль продавца найден', 
                    ['user_id' => $userId, 'seller_profile_id' => $result['id']],
                    'users.log'
                );
                return $result;
            }
            
            // Если профиль не найден, логгируем это
            Application::$app->logger->warning(
                'Профиль продавца не найден', 
                ['user_id' => $userId],
                'users.log'
            );
            
            $user = Application::$app->session->getUser();
            if (!$user) {
                Application::$app->logger->error(
                    'Пользователь не авторизован при попытке получить профиль продавца', 
                    ['user_id' => $userId],
                    'users.log'
                );
                return null;
            }
            
            // Проверяем роль пользователя
            $roles = $user->getRoles();
            Application::$app->logger->info(
                'Проверка ролей пользователя', 
                ['user_id' => $userId, 'roles' => $roles],
                'users.log'
            );
            
            // Проверяем, есть ли у пользователя роль продавца
            $hasSellerRole = false;
            foreach ($roles as $role) {
                if ($role['name'] === 'seller' || (isset($role['id']) && (int)$role['id'] === 2)) {
                    $hasSellerRole = true;
                    break;
                }
            }
            
            if ($hasSellerRole) {
                Application::$app->logger->info(
                    'Создание профиля продавца для пользователя с ролью продавца', 
                    ['user_id' => $userId],
                    'users.log'
                );
                
                try {
                    // Создаем профиль продавца в транзакции
                    Application::$app->db->beginTransaction();
                    
                    // Используем только поля, которые существуют в таблице
                    $insertStmt = Application::$app->db->prepare("
                        INSERT INTO seller_profiles (user_id, seller_type, min_order_amount, created_at, updated_at)
                        VALUES (:user_id, 'ordinary', 0, NOW(), NOW())
                    ");
                    $insertResult = $insertStmt->execute([
                        'user_id' => $userId
                    ]);
                    
                    if (!$insertResult) {
                        Application::$app->logger->error(
                            'Ошибка при создании профиля продавца', 
                            ['user_id' => $userId, 'error' => Application::$app->db->errorInfo()],
                            'errors.log'
                        );
                        Application::$app->db->rollBack();
                        return null;
                    }
                    
                    Application::$app->db->commit();
                    
                    // Получаем созданный профиль продавца
                    $statement = Application::$app->db->prepare("
                        SELECT * FROM seller_profiles 
                        WHERE user_id = :user_id
                    ");
                    $statement->execute(['user_id' => $userId]);
                    $result = $statement->fetch();
                    
                    if ($result) {
                        Application::$app->logger->info(
                            'Профиль продавца успешно создан', 
                            ['user_id' => $userId, 'seller_profile_id' => $result['id']],
                            'users.log'
                        );
                        return $result;
                    } else {
                        Application::$app->logger->error(
                            'Не удалось получить созданный профиль продавца', 
                            ['user_id' => $userId],
                            'errors.log'
                        );
                    }
                } catch (\Exception $e) {
                    Application::$app->logger->error(
                        'Исключение при создании профиля продавца: ' . $e->getMessage(), 
                        ['user_id' => $userId, 'exception' => $e->getTraceAsString()],
                        'errors.log'
                    );
                    
                    // Проверяем, находимся ли мы в транзакции перед откатом
                    if (Application::$app->db->inTransaction()) {
                        Application::$app->db->rollBack();
                    }
                }
            } else {
                Application::$app->logger->warning(
                    'Пользователь не имеет роли продавца', 
                    ['user_id' => $userId, 'roles' => $roles],
                    'users.log'
                );
            }
            
            return null;
        } catch (\Exception $e) {
            Application::$app->logger->error(
                'Исключение при получении профиля продавца: ' . $e->getMessage(), 
                ['user_id' => $userId, 'exception' => $e->getTraceAsString()],
                'errors.log'
            );
            return null;
        }
    }

    private function getSellerStats(int $userId) {
        // Get total orders, revenue, and active products
        $statement = Application::$app->db->prepare("
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_revenue,
                (SELECT COUNT(*) FROM products p 
                 WHERE p.seller_profile_id = sp.id AND p.is_active = 1) as active_products
            FROM seller_profiles sp
            LEFT JOIN orders o ON o.seller_profile_id = sp.id
            WHERE sp.user_id = :user_id
            GROUP BY sp.id
        ");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch();
    }

    private function getRecentOrders(int $userId) {
        $statement = Application::$app->db->prepare("
            SELECT o.*, u.full_name as buyer_name
            FROM orders o
            JOIN seller_profiles sp ON o.seller_profile_id = sp.id
            JOIN users u ON o.buyer_id = u.id
            WHERE sp.user_id = :user_id
            ORDER BY o.order_date DESC
            LIMIT 5
        ");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }
}
