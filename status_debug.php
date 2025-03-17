<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use App\Models\User;

// Инициализация приложения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = [
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD'],
    ]
];

$app = new Application(__DIR__, $config);

// Получаем ID пользователя из GET-параметра
$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Обработка формы изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];
    
    if (in_array($newStatus, ['active', 'pending', 'suspended'])) {
        $db = Application::$app->db;
        $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
        $result = $stmt->execute([
            'id' => $userId,
            'status' => $newStatus
        ]);
        
        if ($result) {
            echo "<div class='alert alert-success'>Статус успешно изменен на: {$newStatus}</div>";
        } else {
            echo "<div class='alert alert-danger'>Ошибка при изменении статуса</div>";
        }
    }
}

// Получаем информацию о пользователе
if ($userId) {
    $db = Application::$app->db;
    
    // Получаем данные пользователя
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        echo "<h1>Пользователь не найден</h1>";
        exit;
    }
    
    // Получаем роли пользователя
    $stmt = $db->prepare("
        SELECT r.* 
        FROM roles r
        JOIN user_roles ur ON r.id = ur.role_id
        WHERE ur.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Выводим информацию и форму для отладки
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Отладка статусов пользователя</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container py-4">
            <h1>Отладка статусов пользователя</h1>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3>Информация о пользователе</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>ID:</th>
                            <td><?= $userData['id'] ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?= $userData['email'] ?></td>
                        </tr>
                        <tr>
                            <th>Имя:</th>
                            <td><?= $userData['full_name'] ?></td>
                        </tr>
                        <tr>
                            <th>Статус:</th>
                            <td>
                                <span class="badge <?= $userData['status'] === 'active' ? 'bg-success' : ($userData['status'] === 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                    <?= $userData['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Роли:</th>
                            <td>
                                <?php foreach ($userRoles as $role): ?>
                                    <span class="badge bg-info"><?= $role['name'] ?></span>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h3>Изменить статус</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="status" class="form-label">Новый статус:</label>
                            <select name="status" id="status" class="form-select">
                                <option value="active" <?= $userData['status'] === 'active' ? 'selected' : '' ?>>Активный</option>
                                <option value="pending" <?= $userData['status'] === 'pending' ? 'selected' : '' ?>>Ожидающий</option>
                                <option value="suspended" <?= $userData['status'] === 'suspended' ? 'selected' : '' ?>>Заблокированный</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Изменить статус</button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h3>Прямой SQL-запрос</h3>
                </div>
                <div class="card-body">
                    <pre><code>UPDATE users SET status = 'active|pending|suspended' WHERE id = <?= $userId ?>;</code></pre>
                    <div class="alert alert-info">
                        Выполните этот запрос напрямую в базе данных, если форма не работает.
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="/admin/users" class="btn btn-secondary">Вернуться к списку пользователей</a>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    echo "<h1>Укажите ID пользователя в параметре ?id=X</h1>";
}
