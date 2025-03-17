<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;

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
$db = Application::$app->db;

// Функция для проверки и исправления пустых статусов
function checkAndFixEmptyStatuses() {
    global $db;
    
    // Проверяем, есть ли пользователи с пустыми статусами
    $stmt = $db->prepare("SELECT id, email, status FROM users WHERE status IS NULL OR status = ''");
    $stmt->execute();
    $usersWithEmptyStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixedCount = 0;
    $errors = [];
    
    if (count($usersWithEmptyStatus) > 0) {
        echo "<div class='alert alert-warning'>Найдено " . count($usersWithEmptyStatus) . " пользователей с пустым статусом.</div>";
        
        foreach ($usersWithEmptyStatus as $user) {
            // Устанавливаем статус 'active' для пользователей с пустым статусом
            $updateStmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = :id");
            $result = $updateStmt->execute(['id' => $user['id']]);
            
            if ($result) {
                $fixedCount++;
                echo "<div class='alert alert-success'>Исправлен пользователь ID: {$user['id']}, Email: {$user['email']} - установлен статус 'active'</div>";
            } else {
                $errors[] = "Не удалось исправить пользователя ID: {$user['id']}, Email: {$user['email']}";
            }
        }
        
        if ($fixedCount > 0) {
            echo "<div class='alert alert-success'>Успешно исправлено $fixedCount пользователей.</div>";
        }
        
        if (!empty($errors)) {
            echo "<div class='alert alert-danger'>Ошибки при исправлении:";
            foreach ($errors as $error) {
                echo "<br>- $error";
            }
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Пользователей с пустым статусом не найдено.</div>";
    }
    
    return $fixedCount;
}

// Функция для проверки статусов всех пользователей
function checkAllUserStatuses() {
    global $db;
    
    $stmt = $db->prepare("SELECT id, email, full_name, status FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statusCounts = [
        'active' => 0,
        'pending' => 0,
        'suspended' => 0,
        'empty' => 0,
        'other' => 0
    ];
    
    $usersWithIssues = [];
    
    foreach ($users as $user) {
        if (empty($user['status'])) {
            $statusCounts['empty']++;
            $usersWithIssues[] = $user;
        } elseif ($user['status'] === 'active') {
            $statusCounts['active']++;
        } elseif ($user['status'] === 'pending') {
            $statusCounts['pending']++;
        } elseif ($user['status'] === 'suspended') {
            $statusCounts['suspended']++;
        } else {
            $statusCounts['other']++;
            $usersWithIssues[] = $user;
        }
    }
    
    return [
        'counts' => $statusCounts,
        'issues' => $usersWithIssues,
        'total' => count($users)
    ];
}

// Функция для исправления проблемы с передачей статусов в форме
function fixStatusFormIssue() {
    // Путь к файлу представления
    $viewFile = __DIR__ . '/src/Views/admin/edit-user.php';
    
    if (file_exists($viewFile)) {
        $content = file_get_contents($viewFile);
        
        // Проверяем, есть ли проблема с атрибутами value в select
        if (strpos($content, '<option value="pending"') !== false) {
            echo "<div class='alert alert-info'>Форма выглядит корректной, значения статусов установлены правильно.</div>";
            return false;
        } else {
            // Исправляем форму, если значения не указаны или указаны неверно
            $updatedContent = preg_replace(
                '/<select class="form-select" id="status" name="status">\s*<option.*?Активный<\/option>\s*<option.*?Ожидает<\/option>\s*<option.*?Заблокирован<\/option>/s',
                '<select class="form-select" id="status" name="status">' . "\n" .
                '                        <option value="active" <?= $user->status === \'active\' ? \'selected\' : \'\'  ?>>Активный</option>' . "\n" .
                '                        <option value="pending" <?= $user->status === \'pending\' ? \'selected\' : \'\'  ?>>Ожидает</option>' . "\n" .
                '                        <option value="suspended" <?= $user->status === \'suspended\' ? \'selected\' : \'\'  ?>>Заблокирован</option>',
                $content
            );
            
            if ($updatedContent !== $content) {
                file_put_contents($viewFile, $updatedContent);
                echo "<div class='alert alert-success'>Форма редактирования пользователя успешно исправлена.</div>";
                return true;
            } else {
                echo "<div class='alert alert-warning'>Не удалось автоматически исправить форму. Пожалуйста, проверьте файл вручную.</div>";
                return false;
            }
        }
    } else {
        echo "<div class='alert alert-danger'>Файл представления не найден: $viewFile</div>";
        return false;
    }
}

// Функция для проверки и исправления обработки статусов в контроллере
function checkAdminController() {
    $controllerFile = __DIR__ . '/src/Controllers/AdminController.php';
    
    if (file_exists($controllerFile)) {
        $content = file_get_contents($controllerFile);
        
        // Проверяем, правильно ли обрабатываются статусы в контроллере
        if (strpos($content, "in_array(\$data['status'], ['active', 'pending', 'suspended'])") !== false) {
            echo "<div class='alert alert-info'>Контроллер правильно проверяет допустимые значения статусов.</div>";
            return true;
        } else {
            echo "<div class='alert alert-warning'>Контроллер может некорректно проверять статусы. Рекомендуется проверить обработку статусов в AdminController.php.</div>";
            return false;
        }
    } else {
        echo "<div class='alert alert-danger'>Файл контроллера не найден: $controllerFile</div>";
        return false;
    }
}

// Функция для тестирования обновления статуса
function testStatusUpdate($userId, $newStatus) {
    global $db;
    
    // Получаем текущий статус
    $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $currentStatus = $stmt->fetchColumn();
    
    echo "<div class='alert alert-info'>Текущий статус пользователя ID: $userId - '$currentStatus'</div>";
    
    // Обновляем статус
    $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
    $result = $stmt->execute([
        'id' => $userId,
        'status' => $newStatus
    ]);
    
    if ($result) {
        // Проверяем, что статус действительно обновился
        $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $updatedStatus = $stmt->fetchColumn();
        
        if ($updatedStatus === $newStatus) {
            echo "<div class='alert alert-success'>Статус успешно обновлен на '$newStatus'</div>";
            return true;
        } else {
            echo "<div class='alert alert-danger'>Статус не обновился. Текущий статус: '$updatedStatus'</div>";
            return false;
        }
    } else {
        echo "<div class='alert alert-danger'>Ошибка при обновлении статуса</div>";
        return false;
    }
}

// Обработка формы
$action = $_POST['action'] ?? '';
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$newStatus = $_POST['new_status'] ?? '';

if ($action === 'fix_empty_statuses') {
    $fixedCount = checkAndFixEmptyStatuses();
} elseif ($action === 'fix_form') {
    $formFixed = fixStatusFormIssue();
} elseif ($action === 'test_status' && $userId > 0 && !empty($newStatus)) {
    $testResult = testStatusUpdate($userId, $newStatus);
}

// Получаем статистику по статусам
$statusStats = checkAllUserStatuses();

// Проверяем контроллер
$controllerOk = checkAdminController();

// HTML шаблон
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Исправление проблем со статусами пользователей</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4">Исправление проблем со статусами пользователей</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Статистика статусов пользователей</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Статус</th>
                                    <th>Количество</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-success">active</span></td>
                                    <td><?= $statusStats['counts']['active'] ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning text-dark">pending</span></td>
                                    <td><?= $statusStats['counts']['pending'] ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger">suspended</span></td>
                                    <td><?= $statusStats['counts']['suspended'] ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">Пустой статус</span></td>
                                    <td><?= $statusStats['counts']['empty'] ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-dark">Другие значения</span></td>
                                    <td><?= $statusStats['counts']['other'] ?></td>
                                </tr>
                                <tr class="table-active">
                                    <td><strong>Всего пользователей</strong></td>
                                    <td><strong><?= $statusStats['total'] ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Исправить пустые статусы</h5>
                    </div>
                    <div class="card-body">
                        <p>Эта функция найдет всех пользователей с пустыми статусами и установит им статус "active".</p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="fix_empty_statuses">
                            <button type="submit" class="btn btn-success">Исправить пустые статусы</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Исправить форму редактирования</h5>
                    </div>
                    <div class="card-body">
                        <p>Эта функция проверит и исправит форму редактирования пользователя, чтобы значения статусов корректно передавались.</p>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="fix_form">
                            <button type="submit" class="btn btn-info">Проверить и исправить форму</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Тестирование обновления статуса</h5>
            </div>
            <div class="card-body">
                <p>Используйте эту форму для тестирования обновления статуса конкретного пользователя.</p>
                <form method="post" action="" class="row g-3">
                    <input type="hidden" name="action" value="test_status">
                    <div class="col-md-4">
                        <label for="user_id" class="form-label">ID пользователя</label>
                        <input type="number" class="form-control" id="user_id" name="user_id" required>
                    </div>
                    <div class="col-md-4">
                        <label for="new_status" class="form-label">Новый статус</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="active">Активный (active)</option>
                            <option value="pending">Ожидающий (pending)</option>
                            <option value="suspended">Заблокированный (suspended)</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Обновить статус</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!empty($statusStats['issues'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Пользователи с проблемными статусами</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Имя</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusStats['issues'] as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td>
                                        <?php if (empty($user['status'])): ?>
                                            <span class="badge bg-secondary">Пустой</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark"><?= htmlspecialchars($user['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="test_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" class="btn btn-sm btn-success me-1">Активный</button>
                                        </form>
                                        
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="test_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="new_status" value="pending">
                                            <button type="submit" class="btn btn-sm btn-warning me-1">Ожидающий</button>
                                        </form>
                                        
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="action" value="test_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="new_status" value="suspended">
                                            <button type="submit" class="btn btn-sm btn-danger">Заблокированный</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="/admin/users" class="btn btn-secondary">Вернуться в админку</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
