<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Preorder;
use App\Models\UserAddress;
use App\Core\Middleware\AuthMiddleware;

/**
 * Контроллер дашборда покупателя
 * 
 * Управляет функциональностью личного кабинета покупателя:
 * - Просмотр заказов
 * - Управление избранными товарами
 * - Управление предзаказами
 * - Настройки профиля
 */
class CustomerDashboardController extends Controller
{
    /**
     * Middleware для проверки авторизации
     */
    public function __construct()
    {
        $this->registerMiddleware(new AuthMiddleware(['index', 'orders', 'wishlist', 'preorders', 'profile']));
    }

    /**
     * Главная страница дашборда покупателя
     */
    public function index()
    {
        // Получение текущего пользователя
        $user = Application::$app->session->getUser();
        
        // Получение последних заказов пользователя (лимит 5)
        $recentOrders = Order::findAll([
            'user_id' => $user->id,
            'ORDER' => 'created_at DESC',
            'LIMIT' => 5
        ]);
        
        // Получение избранных товаров пользователя (лимит 4)
        $wishlistItems = Wishlist::findAll([
            'user_id' => $user->id,
            'LIMIT' => 4
        ]);
        
        // Получение товаров из избранного
        $wishlistProducts = [];
        foreach ($wishlistItems as $item) {
            $product = Product::findOne($item->product_id);
            if ($product) {
                $wishlistProducts[] = $product;
            }
        }
        
        // Получение предзаказов пользователя (лимит 4)
        $preorders = Preorder::findAll([
            'user_id' => $user->id,
            'LIMIT' => 4
        ]);
        
        // Получение товаров из предзаказов
        $preorderProducts = [];
        foreach ($preorders as $preorder) {
            $product = Product::findOne($preorder->product_id);
            if ($product) {
                $preorderProducts[] = $product;
            }
        }
        
        // Логирование действия
        Application::$app->logger->info(
            'User accessed customer dashboard', 
            ['user_id' => $user->id],
            'users.log'
        );
        
        return $this->render('customer/dashboard/index', [
            'user' => $user,
            'recentOrders' => $recentOrders,
            'wishlistProducts' => $wishlistProducts,
            'preorderProducts' => $preorderProducts
        ]);
    }

    /**
     * Страница со всеми заказами пользователя
     */
    public function orders()
    {
        $user = Application::$app->session->getUser();
        
        // Получение всех заказов пользователя
        $orders = Order::findAll([
            'user_id' => $user->id,
            'ORDER' => 'created_at DESC'
        ]);
        
        // Логирование действия
        Application::$app->logger->info(
            'User viewed orders history', 
            ['user_id' => $user->id],
            'users.log'
        );
        
        return $this->render('customer/dashboard/orders', [
            'user' => $user,
            'orders' => $orders
        ]);
    }

    /**
     * Просмотр деталей заказа
     */
    public function viewOrder(Request $request)
    {
        $user = Application::$app->session->getUser();
        $orderId = $request->getRouteParam('id');
        
        // Получение заказа с проверкой принадлежности пользователю
        $order = Order::findOne([
            'id' => $orderId,
            'user_id' => $user->id
        ]);
        
        if (!$order) {
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Заказ не найден или у вас нет доступа к нему'
                ]);
                exit;
            }
            
            Application::$app->session->setFlash('error', 'Заказ не найден или у вас нет доступа к нему');
            return Application::$app->response->redirect('/customer/orders');
        }
        
        // Получение товаров заказа
        $orderItems = OrderItem::findAll([
            'order_id' => $order->id
        ]);
        
        // Получение информации о товарах
        $products = [];
        foreach ($orderItems as $item) {
            $product = Product::findOne($item->product_id);
            if ($product) {
                $products[] = [
                    'product' => $product,
                    'quantity' => $item->quantity,
                    'price' => $item->price
                ];
            }
        }
        
        // Логирование действия
        Application::$app->logger->info(
            'User viewed order details', 
            ['user_id' => $user->id, 'order_id' => $order->id],
            'users.log'
        );
        
        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'html' => $this->renderPartial('customer/dashboard/order_details', [
                    'order' => $order,
                    'products' => $products
                ])
            ]);
            exit;
        }
        
        return $this->render('customer/dashboard/order_details', [
            'user' => $user,
            'order' => $order,
            'products' => $products
        ]);
    }

    /**
     * Страница с избранными товарами
     */
    public function wishlist()
    {
        $user = Application::$app->session->getUser();
        
        // Получение всех избранных товаров пользователя
        $wishlistItems = Wishlist::findAll([
            'user_id' => $user->id
        ]);
        
        // Получение информации о товарах
        $products = [];
        foreach ($wishlistItems as $item) {
            $product = Product::findOne($item->product_id);
            if ($product) {
                $products[] = $product;
            }
        }
        
        // Логирование действия
        Application::$app->logger->info(
            'User viewed wishlist', 
            ['user_id' => $user->id],
            'users.log'
        );
        
        return $this->render('customer/dashboard/wishlist', [
            'user' => $user,
            'products' => $products
        ]);
    }

    /**
     * Добавление товара в избранное
     */
    public function addToWishlist(Request $request)
    {
        $user = Application::$app->session->getUser();
        
        if ($request->isPost() && $request->isAjax()) {
            $productId = $request->getBody()['product_id'] ?? null;
            
            if (!$productId) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Не указан ID товара'
                ]);
                exit;
            }
            
            // Проверка существования товара
            $product = Product::findOne($productId);
            if (!$product) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Товар не найден'
                ]);
                exit;
            }
            
            // Проверка, есть ли уже товар в избранном
            $existingItem = Wishlist::findOne([
                'user_id' => $user->id,
                'product_id' => $productId
            ]);
            
            if ($existingItem) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Товар уже в избранном'
                ]);
                exit;
            }
            
            // Добавление товара в избранное
            $wishlistItem = new Wishlist();
            $wishlistItem->user_id = $user->id;
            $wishlistItem->product_id = $productId;
            $wishlistItem->created_at = date('Y-m-d H:i:s');
            
            if ($wishlistItem->save()) {
                // Логирование действия
                Application::$app->logger->info(
                    'User added product to wishlist', 
                    ['user_id' => $user->id, 'product_id' => $productId],
                    'users.log'
                );
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Товар добавлен в избранное'
                ]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Не удалось добавить товар в избранное'
                ]);
                exit;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Неверный запрос'
        ]);
        exit;
    }

    /**
     * Удаление товара из избранного
     */
    public function removeFromWishlist(Request $request)
    {
        $user = Application::$app->session->getUser();
        
        if ($request->isPost() && $request->isAjax()) {
            $productId = $request->getBody()['product_id'] ?? null;
            
            if (!$productId) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Не указан ID товара'
                ]);
                exit;
            }
            
            // Поиск записи в избранном
            $wishlistItem = Wishlist::findOne([
                'user_id' => $user->id,
                'product_id' => $productId
            ]);
            
            if (!$wishlistItem) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Товар не найден в избранном'
                ]);
                exit;
            }
            
            // Удаление из избранного
            if ($wishlistItem->delete()) {
                // Логирование действия
                Application::$app->logger->info(
                    'User removed product from wishlist', 
                    ['user_id' => $user->id, 'product_id' => $productId],
                    'users.log'
                );
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Товар удален из избранного'
                ]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Не удалось удалить товар из избранного'
                ]);
                exit;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Неверный запрос'
        ]);
        exit;
    }

    /**
     * Страница с предзаказами
     */
    public function preorders()
    {
        $user = Application::$app->session->getUser();
        
        // Получение всех предзаказов пользователя
        $preorderItems = Preorder::findAll([
            'user_id' => $user->id
        ]);
        
        // Получение информации о товарах
        $products = [];
        foreach ($preorderItems as $item) {
            $product = Product::findOne($item->product_id);
            if ($product) {
                $products[] = [
                    'product' => $product,
                    'preorder' => $item
                ];
            }
        }
        
        // Логирование действия
        Application::$app->logger->info(
            'User viewed preorders', 
            ['user_id' => $user->id],
            'users.log'
        );
        
        return $this->render('customer/dashboard/preorders', [
            'user' => $user,
            'products' => $products
        ]);
    }

    /**
     * Страница профиля пользователя
     */
    public function profile()
    {
        $user = Application::$app->session->getUser();
        
        // Получение адресов пользователя
        $addresses = UserAddress::findAll([
            'user_id' => $user->id,
            'ORDER' => 'is_default DESC, id ASC'
        ]);
        
        // Логирование действия
        Application::$app->logger->info(
            'User viewed profile', 
            ['user_id' => $user->id],
            'users.log'
        );
        
        return $this->render('customer/dashboard/profile', [
            'user' => $user,
            'addresses' => $addresses
        ]);
    }

    /**
     * Обновление профиля пользователя
     */
    public function updateProfile()
    {
        $request = Application::$app->request;
        $user = Application::$app->session->getUser();
        
        if ($request->isPost()) {
            $userData = $request->getBody();
            
            // Обновление данных пользователя
            $user->full_name = $userData['name'] ?? $user->full_name;
            $user->email = $userData['email'] ?? $user->email;
            $user->phone = $userData['phone'] ?? $user->phone;
            
            // Настройки уведомлений
            $user->notification_order = isset($userData['notification_order']) ? 1 : 0;
            $user->notification_promo = isset($userData['notification_promo']) ? 1 : 0;
            $user->notification_system = isset($userData['notification_system']) ? 1 : 0;
            
            // Проверка на изменение пароля
            if (!empty($userData['password']) && !empty($userData['password_confirm'])) {
                if ($userData['password'] !== $userData['password_confirm']) {
                    if ($request->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Пароли не совпадают'
                        ]);
                        exit;
                    }
                    
                    Application::$app->session->setFlash('error', 'Пароли не совпадают');
                    return $this->render('customer/dashboard/profile', [
                        'user' => $user
                    ]);
                }
                
                $user->password = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            // Обработка загрузки аватара
            $avatar = $request->getFiles()['avatar'] ?? null;
            if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!in_array($avatar['type'], $allowedTypes)) {
                    if ($request->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Недопустимый формат файла. Разрешены только JPEG, PNG и GIF'
                        ]);
                        exit;
                    }
                    
                    Application::$app->session->setFlash('error', 'Недопустимый формат файла. Разрешены только JPEG, PNG и GIF');
                    return $this->render('customer/dashboard/profile', [
                        'user' => $user
                    ]);
                }
                
                if ($avatar['size'] > $maxSize) {
                    if ($request->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Размер файла превышает 2MB'
                        ]);
                        exit;
                    }
                    
                    Application::$app->session->setFlash('error', 'Размер файла превышает 2MB');
                    return $this->render('customer/dashboard/profile', [
                        'user' => $user
                    ]);
                }
                
                // Создание директории для аватаров, если она не существует
                $uploadDir = Application::$ROOT_DIR . '/public/uploads/avatars/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Генерация уникального имени файла
                $fileExtension = pathinfo($avatar['name'], PATHINFO_EXTENSION);
                $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;
                
                // Перемещение загруженного файла
                if (move_uploaded_file($avatar['tmp_name'], $filePath)) {
                    // Удаление старого аватара, если он существует
                    if ($user->avatar && file_exists($uploadDir . $user->avatar)) {
                        unlink($uploadDir . $user->avatar);
                    }
                    
                    $user->avatar = $fileName;
                } else {
                    if ($request->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => 'Не удалось загрузить аватар'
                        ]);
                        exit;
                    }
                    
                    Application::$app->session->setFlash('error', 'Не удалось загрузить аватар');
                    return $this->render('customer/dashboard/profile', [
                        'user' => $user
                    ]);
                }
            }
            
            if ($user->save()) {
                // Логирование действия
                Application::$app->logger->info(
                    'User updated profile', 
                    ['user_id' => $user->id],
                    'users.log'
                );
                
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Профиль успешно обновлен'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('success', 'Профиль успешно обновлен');
                return Application::$app->response->redirect('/customer/profile');
            } else {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не удалось обновить профиль'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Не удалось обновить профиль');
            }
        }
        
        // Получение адресов пользователя
        $addresses = UserAddress::findAll([
            'user_id' => $user->id,
            'ORDER' => 'is_default DESC, id ASC'
        ]);
        
        return $this->render('customer/dashboard/profile', [
            'user' => $user,
            'addresses' => $addresses
        ]);
    }
    
    /**
     * Добавление нового адреса доставки
     */
    public function addAddress()
    {
        $request = Application::$app->request;
        $user = Application::$app->session->getUser();
        
        if ($request->isPost()) {
            $addressData = $request->getBody();
            
            $address = new UserAddress();
            $address->user_id = $user->id;
            $address->title = $addressData['title'] ?? '';
            $address->recipient_name = $addressData['recipient_name'] ?? '';
            $address->phone = $addressData['phone'] ?? '';
            $address->country = $addressData['country'] ?? '';
            $address->city = $addressData['city'] ?? '';
            $address->street = $addressData['street'] ?? '';
            $address->house = $addressData['house'] ?? '';
            $address->apartment = $addressData['apartment'] ?? '';
            $address->postal_code = $addressData['postal_code'] ?? '';
            $address->is_default = isset($addressData['is_default']) ? 1 : 0;
            
            // Если адрес отмечен как основной, сбрасываем флаг у других адресов
            if ($address->is_default) {
                UserAddress::updateAll(
                    ['is_default' => 0],
                    ['user_id' => $user->id]
                );
            }
            
            // Если это первый адрес пользователя, делаем его основным
            $existingAddresses = UserAddress::findAll(['user_id' => $user->id]);
            if (empty($existingAddresses)) {
                $address->is_default = 1;
            }
            
            // Исправленный метод addAddress
            $db = Application::$app->db;
            $sql = "INSERT INTO user_addresses (user_id, title, recipient_name, phone, country, city, street, house, apartment, postal_code, is_default, created_at, updated_at) 
                   VALUES (:user_id, :title, :recipient_name, :phone, :country, :city, :street, :house, :apartment, :postal_code, :is_default, NOW(), NOW())";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                'user_id' => $address->user_id,
                'title' => $address->title,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'country' => $address->country,
                'city' => $address->city,
                'street' => $address->street,
                'house' => $address->house,
                'apartment' => $address->apartment,
                'postal_code' => $address->postal_code,
                'is_default' => $address->is_default
            ]);
            
            if ($result) {
                // Получаем ID созданного адреса
                $address->id = $db->lastInsertId();
                
                // Логирование действия
                Application::$app->logger->info(
                    'User added new address', 
                    ['user_id' => $user->id, 'address_id' => $address->id],
                    'users.log'
                );
                
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Адрес успешно добавлен',
                        'address' => $address
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('success', 'Адрес успешно добавлен');
                return Application::$app->response->redirect('/customer/profile');
            } else {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не удалось добавить адрес'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Не удалось добавить адрес');
            }
        }
        
        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Неверный запрос'
            ]);
            exit;
        }
        
        return Application::$app->response->redirect('/customer/profile');
    }
    
    /**
     * Обновление адреса доставки
     */
    public function updateAddress()
    {
        $request = Application::$app->request;
        $user = Application::$app->session->getUser();
        
        if ($request->isPost()) {
            $addressData = $request->getBody();
            $addressId = $addressData['address_id'] ?? null;
            
            if (!$addressId) {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не указан ID адреса'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Не указан ID адреса');
                return Application::$app->response->redirect('/customer/profile');
            }
            
            // Получение адреса с проверкой принадлежности пользователю
            $address = UserAddress::findOne([
                'id' => $addressId,
                'user_id' => $user->id
            ]);
            
            if (!$address) {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Адрес не найден или у вас нет доступа к нему'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Адрес не найден или у вас нет доступа к нему');
                return Application::$app->response->redirect('/customer/profile');
            }
            
            // Обновление данных адреса
            $address->title = $addressData['title'] ?? $address->title;
            $address->recipient_name = $addressData['recipient_name'] ?? $address->recipient_name;
            $address->phone = $addressData['phone'] ?? $address->phone;
            $address->country = $addressData['country'] ?? $address->country;
            $address->city = $addressData['city'] ?? $address->city;
            $address->street = $addressData['street'] ?? $address->street;
            $address->house = $addressData['house'] ?? $address->house;
            $address->apartment = $addressData['apartment'] ?? $address->apartment;
            $address->postal_code = $addressData['postal_code'] ?? $address->postal_code;
            
            // Обработка флага основного адреса
            if (isset($addressData['is_default'])) {
                // Сбрасываем флаг основного адреса у других адресов
                $resetSql = "UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id";
                $resetStmt = $db->prepare($resetSql);
                $resetStmt->execute(['user_id' => $user->id]);
                
                $address->is_default = 1;
            }
            
            // Обновление адреса в базе данных
            $db = Application::$app->db;
            $sql = "UPDATE user_addresses SET 
                    title = :title, 
                    recipient_name = :recipient_name, 
                    phone = :phone, 
                    country = :country, 
                    city = :city, 
                    street = :street, 
                    house = :house, 
                    apartment = :apartment, 
                    postal_code = :postal_code, 
                    is_default = :is_default, 
                    updated_at = NOW() 
                    WHERE id = :id AND user_id = :user_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                'id' => $address->id,
                'user_id' => $user->id,
                'title' => $address->title,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'country' => $address->country,
                'city' => $address->city,
                'street' => $address->street,
                'house' => $address->house,
                'apartment' => $address->apartment,
                'postal_code' => $address->postal_code,
                'is_default' => $address->is_default
            ]);
            
            if ($result) {
                // Логирование действия
                Application::$app->logger->info(
                    'User updated address', 
                    ['user_id' => $user->id, 'address_id' => $address->id],
                    'users.log'
                );
                
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Адрес успешно обновлен',
                        'address' => $address
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('success', 'Адрес успешно обновлен');
                return Application::$app->response->redirect('/customer/profile');
            } else {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не удалось обновить адрес'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Не удалось обновить адрес');
            }
        }
        
        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Неверный запрос'
            ]);
            exit;
        }
        
        return Application::$app->response->redirect('/customer/profile');
    }

    /**
     * Удаление адреса доставки
     */
    public function deleteAddress()
    {
        $request = Application::$app->request;
        $user = Application::$app->session->getUser();
        
        if ($request->isPost()) {
            $addressData = $request->getBody();
            $addressId = $addressData['address_id'] ?? null;
            
            if (!$addressId) {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не указан ID адреса'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Не указан ID адреса');
                return Application::$app->response->redirect('/customer/profile');
            }
            
            // Получение адреса с проверкой принадлежности пользователю
            $address = UserAddress::findOne([
                'id' => $addressId,
                'user_id' => $user->id
            ]);
            
            if (!$address) {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Адрес не найден или у вас нет доступа к нему'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Адрес не найден или у вас нет доступа к нему');
                return Application::$app->response->redirect('/customer/profile');
            }
            
            // Проверка, является ли адрес основным
            $isDefault = $address->is_default;
            
            // Удаление адреса из базы данных
            $db = Application::$app->db;
            $sql = "DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                'id' => $addressId,
                'user_id' => $user->id
            ]);
            
            if ($result) {
                // Логирование действия
                Application::$app->logger->info(
                    'User deleted address', 
                    ['user_id' => $user->id, 'address_id' => $addressId],
                    'users.log'
                );
                
                // Если удаленный адрес был основным, устанавливаем новый основной адрес
                if ($isDefault) {
                    // Логирование действия
                    Application::$app->logger->info(
                        'User set new default address', 
                        ['user_id' => $user->id],
                        'users.log'
                    );
                    
                    // Получаем первый доступный адрес пользователя
                    $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY id ASC LIMIT 1";
                    $stmt = $db->prepare($sql);
                    $stmt->execute(['user_id' => $user->id]);
                    $firstAddress = $stmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if ($firstAddress) {
                        // Обновляем первый адрес как основной
                        $updateSql = "UPDATE user_addresses SET is_default = 1, updated_at = NOW() WHERE id = :id";
                        $updateStmt = $db->prepare($updateSql);
                        $updateStmt->execute(['id' => $firstAddress['id']]);
                    }
                }
                
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Адрес успешно удален'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('success', 'Адрес успешно удален');
                return Application::$app->response->redirect('/customer/profile');
            } else {
                if ($request->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Не удалось удалить адрес'
                    ]);
                    exit;
                }
                
                Application::$app->session->setFlash('error', 'Не удалось удалить адрес');
            }
        }
        
        if ($request->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Неверный запрос'
            ]);
            exit;
        }
        
        return Application::$app->response->redirect('/customer/profile');
    }
}
