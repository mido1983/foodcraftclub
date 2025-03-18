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
                    'errors.log'
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
                $imageUrl = $this->processUploadedImage($_FILES['image'], $user->id, 'products');
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
        
        // Логирование полученных данных
        Application::$app->logger->info(
            'Получены данные для редактирования продукта', 
            $data,
            'products.log'
        );
        
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
        $validationErrors = [];
        
        if (empty($data['product_name'])) {
            $validationErrors[] = 'Название продукта обязательно';
        }
        
        if (empty($data['description'])) {
            $validationErrors[] = 'Описание продукта обязательно';
        }
        
        if (!isset($data['price']) || $data['price'] === '') {
            $validationErrors[] = 'Цена продукта обязательна';
        }
        
        if (!empty($validationErrors)) {
            Application::$app->logger->error(
                'Ошибки валидации при редактировании продукта', 
                ['errors' => $validationErrors, 'data' => $data],
                'products.log'
            );
            Application::$app->session->setFlash('error', 'Please fill in all required fields: ' . implode(', ', $validationErrors));
            $this->redirect('/seller/products');
            return '';
        }
        
        // Обработка загруженного изображения
        $imageUrl = null; // По умолчанию изображение не задано
        
        // Получаем текущее изображение из таблицы product_images
        $imageStatement = Application::$app->db->prepare("
            SELECT image_url FROM product_images 
            WHERE product_id = :product_id AND is_main = 1
        ");
        $imageStatement->execute(['product_id' => $data['id']]);
        $currentImage = $imageStatement->fetch(PDO::FETCH_ASSOC);
        
        if ($currentImage) {
            $imageUrl = $currentImage['image_url'];
        }
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $newImageUrl = $this->processUploadedImage($_FILES['image'], $user->id, 'products', $imageUrl);
            
            if ($newImageUrl) {
                // Если есть изображение, обновляем его в таблице product_images
                if ($currentImage) {
                    $updateImageStmt = Application::$app->db->prepare("
                        UPDATE product_images 
                        SET image_url = :image_url 
                        WHERE product_id = :product_id AND is_main = 1
                    ");
                    $updateImageStmt->execute([
                        'image_url' => $newImageUrl,
                        'product_id' => $data['id']
                    ]);
                    
                    Application::$app->logger->info(
                        'Обновлено изображение для продукта', 
                        ['product_id' => $data['id'], 'old_image' => $imageUrl, 'new_image' => $newImageUrl],
                        'products.log'
                    );
                } else {
                    // Если изображения нет, добавляем его в таблицу product_images
                    $insertImageStmt = Application::$app->db->prepare("
                        INSERT INTO product_images (product_id, image_url, is_main) 
                        VALUES (:product_id, :image_url, 1)
                    ");
                    $insertImageStmt->execute([
                        'product_id' => $data['id'],
                        'image_url' => $newImageUrl
                    ]);
                    
                    Application::$app->logger->info(
                        'Добавлено изображение для продукта', 
                        ['product_id' => $data['id'], 'image_url' => $newImageUrl],
                        'products.log'
                    );
                }
                
                $imageUrl = $newImageUrl;
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
            // Логирование SQL запроса и параметров
            $sql = "
                UPDATE products 
                SET product_name = :product_name, 
                    description = :description, 
                    price = :price, 
                    category_id = :category_id, 
                    is_active = :is_active, 
                    available_for_preorder = :available_for_preorder
                WHERE id = :id AND seller_profile_id = :seller_profile_id
            ";
            
            $params = [
                'id' => $data['id'],
                'product_name' => $data['product_name'],
                'description' => $data['description'],
                'price' => (float)$data['price'],
                'category_id' => $categoryId,
                'is_active' => $isActive,
                'available_for_preorder' => $availableForPreorder,
                'seller_profile_id' => $sellerProfile['id']
            ];
            
            Application::$app->logger->info(
                'SQL запрос на обновление продукта', 
                ['sql' => $sql, 'params' => $params],
                'products.log'
            );
            
            $statement = Application::$app->db->prepare($sql);
            $result = $statement->execute($params);
            
            if ($result) {
                Application::$app->logger->info(
                    'Продукт успешно обновлен', 
                    ['user_id' => $user->id, 'product_id' => $data['id'], 'product_name' => $data['product_name']],
                    'products.log'
                );
                Application::$app->session->setFlash('success', 'Product updated successfully');
            } else {
                Application::$app->session->setFlash('error', 'Failed to update product');
                Application::$app->logger->error(
                    'Failed to update product', 
                    ['user_id' => $user->id, 'product_id' => $data['id'], 'product_name' => $data['product_name']],
                    'products.log'
                );
            }
        } catch (\Exception $e) {
            Application::$app->session->setFlash('error', 'An error occurred: ' . $e->getMessage());
            Application::$app->logger->error(
                'Exception when updating product: ' . $e->getMessage(), 
                ['exception' => $e, 'trace' => $e->getTraceAsString(), 'data' => $data],
                'products.log'
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

    /**
     * Страница профиля продавца
     * @return string
     */
    public function profile() {
        $this->view->title = 'Профиль продавца';
        
        $user = $this->getUserProfile();
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->session->setFlash('error', 'Профиль продавца не найден');
            $this->redirect('/seller');
            return '';
        }
        
        // Если форма отправлена, обрабатываем обновление профиля
        if ($this->request->isPost()) {
            $data = $this->request->getBody();
            
            // Валидация данных
            $validationErrors = [];
            
            if (empty($data['name'])) {
                $validationErrors[] = 'Название магазина обязательно';
            }
            
            if (empty($data['description'])) {
                $validationErrors[] = 'Описание магазина обязательно';
            }
            
            if (!empty($validationErrors)) {
                Application::$app->session->setFlash('error', 'Пожалуйста, заполните все обязательные поля: ' . implode(', ', $validationErrors));
            } else {
                // Обработка загруженного изображения для аватара
                $avatarUrl = $sellerProfile['avatar_url'];
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $avatarUrl = $this->processUploadedImage($_FILES['avatar'], $user->id, 'avatars', $avatarUrl);
                }
                
                // Обновляем профиль продавца
                $statement = Application::$app->db->prepare("
                    UPDATE seller_profiles 
                    SET name = :name, 
                        description = :description, 
                        email = :email, 
                        phone = :phone, 
                        avatar_url = :avatar_url,
                        updated_at = NOW() 
                    WHERE id = :id
                ");
                
                $statement->execute([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'avatar_url' => $avatarUrl,
                    'id' => $sellerProfile['id']
                ]);
                
                Application::$app->session->setFlash('success', 'Профиль успешно обновлен');
                $this->redirect('/seller/profile');
                return '';
            }
        }
        
        // Получаем все доступные способы оплаты
        $paymentMethods = $this->getPaymentMethods();
        
        // Получаем выбранные способы оплаты для текущего продавца
        $sellerPaymentOptions = $this->getSellerPaymentOptions($sellerProfile['id']);
        
        return $this->render('seller/profile/index', [
            'user' => $user,
            'sellerProfile' => $sellerProfile,
            'paymentMethods' => $paymentMethods,
            'sellerPaymentOptions' => $sellerPaymentOptions
        ]);
    }

    /**
     * Получаем все доступные способы оплаты
     * @return array
     */
    private function getPaymentMethods() {
        $statement = Application::$app->db->prepare("
            SELECT * FROM payment_methods
        ");
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Получаем выбранные способы оплаты для текущего продавца
     * @param int $sellerProfileId
     * @return array
     */
    private function getSellerPaymentOptions(int $sellerProfileId) {
        $statement = Application::$app->db->prepare("
            SELECT payment_method_id FROM seller_payment_options
            WHERE seller_profile_id = :seller_profile_id
        ");
        $statement->execute(['seller_profile_id' => $sellerProfileId]);
        $options = $statement->fetchAll(PDO::FETCH_COLUMN);
        return $options;
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

    /**
     * Обрабатывает загруженное изображение с учетом требований
     * @param array $file Загруженный файл из $_FILES
     * @param int $userId ID пользователя для создания персональной директории
     * @param string $type Тип изображения (products, avatars, etc.)
     * @param string|null $oldImageUrl URL старого изображения (для редактирования)
     * @param string|null $altText Альтернативный текст для изображения
     * @return string URL нового изображения или дефолтного
     */
    private function processUploadedImage($file, $userId, $type = 'products', $oldImageUrl = null, $altText = null) {
        // Проверяем, загружен ли файл
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return $oldImageUrl ?: '/assets/images/default-' . $type . '.svg';
        }
        
        // Базовая директория для загрузок
        $baseUploadDir = Application::$app->rootPath . '/public/uploads/';
        
        // Создаем директорию пользователя
        $userDir = $baseUploadDir . $userId . '/';
        if (!file_exists($userDir)) {
            mkdir($userDir, 0777, true);
        }
        
        // Создаем директорию для типа файлов
        $typeDir = $userDir . $type . '/';
        if (!file_exists($typeDir)) {
            mkdir($typeDir, 0777, true);
        }
        
        // Проверяем тип файла
        $allowedTypes = ['image/avif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            Application::$app->session->setFlash('error', 'Invalid file type. Only AVIF and WebP are allowed.');
            return $oldImageUrl ?: '/assets/images/default-' . $type . '.svg';
        }
        
        // Проверяем размер файла (макс. 100KB)
        if ($file['size'] > 100 * 1024) {
            Application::$app->session->setFlash('error', 'File is too large. Maximum size is 100KB.');
            return $oldImageUrl ?: '/assets/images/default-' . $type . '.svg';
        }
        
        // Убедимся, что расширение файла корректное (avif или webp)
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        if (!in_array($extension, ['avif', 'webp'])) {
            $extension = 'webp'; // По умолчанию используем webp
        }
        
        // Создаем чистое имя файла без пробелов и специальных символов
        $hash = md5($file['name'] . time() . rand(1000, 9999));
        $fileName = $type . '_' . time() . '_' . $hash . '.' . $extension;
        $uploadFile = $typeDir . $fileName;
        
        // Перемещаем файл
        if (!move_uploaded_file($file['tmp_name'], $uploadFile)) {
            Application::$app->logger->error(
                'Failed to upload image', 
                ['user_id' => $userId, 'file' => $file['name'], 'target' => $uploadFile],
                'uploads.log'
            );
            return $oldImageUrl ?: '/assets/images/default-' . $type . '.svg';
        }
        
        // Удаляем старое изображение, если оно существует и не является дефолтным
        if ($oldImageUrl && strpos($oldImageUrl, 'default-') === false) {
            $oldImagePath = Application::$app->rootPath . '/public' . $oldImageUrl;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
        
        // Логируем успешную загрузку
        Application::$app->logger->info(
            'Uploaded image successfully', 
            [
                'user_id' => $userId, 
                'file' => $fileName, 
                'type' => $type, 
                'alt_text' => $altText
            ],
            'uploads.log'
        );
        
        // Возвращаем URL для сохранения в базе данных
        return '/uploads/' . $userId . '/' . $type . '/' . $fileName;
    }

    /**
     * u0418u0441u043fu0440u0430u0432u043bu0435u043du0438u0435 u043cu0435u0442u043eu0434u0430 editProduct u0434u043bu044f u0440u0430u0431u043eu0442u044b u0441 u0442u0430u0431u043bu0438u0446u0435u0439 product_images u0432u043cu0435u0441u0442u043e u043au043eu043bu043eu043du043au0438 image_url u0432 u0442u0430u0431u043bu0438u0446u0435 products
     * @return string
     */
    public function fixProductImages() {
        $user = $this->getUserProfile();
        $sellerProfile = $this->getSellerProfile($user->id);
        
        if (!$sellerProfile) {
            Application::$app->session->setFlash('error', 'u041fu0440u043eu0444u0438u043bu044c u043fu0440u043eu0434u0430u0432u0446u0430 u043du0435 u043du0430u0439u0434u0435u043d');
            $this->redirect('/seller');
            return '';
        }
        
        // u0421u043eu0437u0434u0430u0435u043c u0434u0435u0444u043eu043bu0442u043du043eu043e u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435, u0435u0441u043bu0438 u043eu043du043e u043du0435 u0441u0443u0447u0435u0441u0442u0432u0443u0435u0442
        $defaultImagePath = Application::$app->rootPath . '/public/assets/images/default-products.svg';
        if (!file_exists($defaultImagePath)) {
            $defaultImageContent = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                <rect width="200" height="200" fill="#f8f9fa"/>
                <text x="50%" y="50%" font-family="Arial" font-size="20" text-anchor="middle" dominant-baseline="middle" fill="#6c757d">No Image</text>
            </svg>';
            
            if (!is_dir(dirname($defaultImagePath))) {
                mkdir(dirname($defaultImagePath), 0777, true);
            }
            
            file_put_contents($defaultImagePath, $defaultImageContent);
        }
        
        // u041fu043eu043blu0443u0447u0430u0435u043c u0432u0441u0435 u043fu0440u043eu0434u0443u043au0442u044b u043fu0440u043eu0434u0430u0432u0446u0430
        $statement = Application::$app->db->prepare("
            SELECT p.id, p.product_name, pi.image_url, pi.id as image_id 
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
            WHERE p.seller_profile_id = :seller_profile_id
        ");
        $statement->execute(['seller_profile_id' => $sellerProfile['id']]);
        $products = $statement->fetchAll();
        
        $fixedCount = 0;
        
        foreach ($products as $product) {
            // u041fu0440u043eu0432u0435u0440u044fu0435u043c u0441u0443u0447u0435u0441u0442u0432u043eu0432u0430u043du0438u0435 u0444u0430u0439u043bu0430 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439 u043fu0440u043eu0434u0443u043au0442u0430
            $imageExists = false;
            
            if (!empty($product['image_url'])) {
                $imagePath = Application::$app->rootPath . '/public' . $product['image_url'];
                $imageExists = file_exists($imagePath);
            }
            
            // u0415u0441u043bu0438 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435 u043du0435 u0441u0443u0447u0435u0441u0442u0432u0443u0435u0442, u0443u0441u0442u0430u043du0430u0432u043bu0438u0432u0430u0435u043c u0434u0435u0444u043eu043bu0442u043du043eu043e
            if (!$imageExists) {
                $defaultImageUrl = '/assets/images/default-products.svg';
                
                if (isset($product['image_id']) && $product['image_id']) {
                    // u041eu0431u043du043eu0432u043bu044fu0435u043c u0441u0443u0447u0435u0441u0442u0432u0443u044eu0447u0443u044e u0437u0430u043fu0438u0441u044c u0432 product_images
                    $updateStmt = Application::$app->db->prepare("
                        UPDATE product_images 
                        SET image_url = :image_url 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        'image_url' => $defaultImageUrl,
                        'id' => $product['image_id']
                    ]);
                    
                    Application::$app->logger->info(
                        'u0418u0441u043fu0440u0430u0432u043bu0435u043du043e u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435 u043fu0440u043eu0434u0443u043au0442u0430', 
                        ['product_id' => $product['id'], 'old_image' => $product['image_url'], 'new_image' => $defaultImageUrl],
                        'products.log'
                    );
                } else {
                    // u0421u043eu0437u0434u0430u0435u043c u043du043eu0432u0443u044e u0437u0430u043fu0438u0441u044c u0432 product_images
                    $insertStmt = Application::$app->db->prepare("
                        INSERT INTO product_images (product_id, image_url, is_main) 
                        VALUES (:product_id, :image_url, 1)
                    ");
                    $insertStmt->execute([
                        'product_id' => $product['id'],
                        'image_url' => $defaultImageUrl
                    ]);
                    
                    Application::$app->logger->info(
                        'u0414u043eu0431u0430u0432u043bu0435u043du043e u0434u0435u0444u043eu043bu0442u043du043eu043e u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435 u043fu0440u043eu0434u0443u043au0442u0430 u0431u0435u0437 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439', 
                        ['product_id' => $product['id'], 'new_image' => $defaultImageUrl],
                        'products.log'
                    );
                }
                
                $fixedCount++;
            }
        }
        
        // u0421u043eu0437u0434u0430u0435u043c u0434u0435u0444u043eu043bu0442u043du043eu043e u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435 u0434u043bu044f u043fu0440u043eu0434u0443u043au0442u0430 u0431u0435u0437 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439
        $noImagesStmt = Application::$app->db->prepare("
            SELECT p.id, p.product_name 
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE p.seller_profile_id = :seller_profile_id AND pi.id IS NULL
        ");
        $noImagesStmt->execute(['seller_profile_id' => $sellerProfile['id']]);
        $productsWithoutImages = $noImagesStmt->fetchAll();
        
        foreach ($productsWithoutImages as $product) {
            // u0421u043eu0437u0434u0430u0435u043c u043du043eu0432u0443u044e u0437u0430u043fu0438u0441u044c u0432 product_images
            $insertStmt = Application::$app->db->prepare("
                INSERT INTO product_images (product_id, image_url, is_main) 
                VALUES (:product_id, :image_url, 1)
            ");
            $insertStmt->execute([
                'product_id' => $product['id'],
                'image_url' => '/assets/images/default-products.svg'
            ]);
            
            Application::$app->logger->info(
                'u0414u043eu0431u0430u0432u043bu0435u043du043e u0434u0435u0444u043eu043bu0442u043du043eu043e u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0435 u043fu0440u043eu0434u0443u043au0442u0430 u0431u0435u0437 u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439', 
                ['product_id' => $product['id'], 'new_image' => '/assets/images/default-products.svg'],
                'products.log'
            );
            
            $fixedCount++;
        }
        
        Application::$app->session->setFlash('success', "u0418u0441u043fu0440u0430u0432u043bu0435u043du043e u0438u0437u043eu0431u0440u0430u0436u0435u043du0438u0439: {$fixedCount}");
        $this->redirect('/seller/products');
        return '';
    }
}
