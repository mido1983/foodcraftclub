<?php
// Скрипт для просмотра логов

// Проверка авторизации (упрощенная версия)
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Путь к директории с логами
$logDir = dirname(__DIR__) . '/logs';

// Получаем список файлов логов
$logFiles = [];
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
            $logFiles[] = $file;
        }
    }
}

// Выбранный файл лога
$selectedLog = isset($_GET['log']) && in_array($_GET['log'], $logFiles) ? $_GET['log'] : (count($logFiles) > 0 ? $logFiles[0] : null);

// Содержимое лога
$logContent = '';
if ($selectedLog) {
    $logPath = $logDir . '/' . $selectedLog;
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
    }
}

// Количество строк для отображения
$maxLines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;

// Фильтр по ключевому слову
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Обработка содержимого лога
if ($logContent) {
    // Разбиваем на строки
    $lines = explode("\n", $logContent);
    
    // Применяем фильтр, если задан
    if ($filter) {
        $lines = array_filter($lines, function($line) use ($filter) {
            return stripos($line, $filter) !== false;
        });
    }
    
    // Берем последние N строк
    $lines = array_slice(array_reverse($lines), 0, $maxLines);
    
    // Переворачиваем обратно для хронологического порядка
    $lines = array_reverse($lines);
    
    // Собираем обратно в строку
    $logContent = implode("\n", $lines);
}

// Функция для подсветки ошибок
function highlightLog($text) {
    // Подсветка уровней логирования
    $text = preg_replace('/\[(DEBUG|INFO|NOTICE)\]/', '<span style="color: #28a745;">[$1]</span>', $text);
    $text = preg_replace('/\[(WARNING)\]/', '<span style="color: #ffc107;">[$1]</span>', $text);
    $text = preg_replace('/\[(ERROR|CRITICAL|ALERT|EMERGENCY)\]/', '<span style="color: #dc3545;">[$1]</span>', $text);
    
    // Подсветка дат
    $text = preg_replace('/\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]/', '<span style="color: #6c757d;">[$1]</span>', $text);
    
    // Подсветка JSON
    $text = preg_replace('/\{.*\}/', '<span style="color: #17a2b8;">$0</span>', $text);
    
    return $text;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр логов - Food Craft Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 70vh;
            overflow-y: auto;
        }
        .log-nav {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 mb-4">
                <h1>Просмотр логов</h1>
                <a href="/seller/products" class="btn btn-primary">Вернуться к продуктам</a>
            </div>
        </div>
        
        <div class="row">
            <!-- Боковая панель с файлами логов -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">Файлы логов</div>
                    <div class="card-body p-0">
                        <div class="list-group log-nav">
                            <?php foreach ($logFiles as $logFile): ?>
                                <a href="?log=<?= urlencode($logFile) ?>&lines=<?= $maxLines ?>&filter=<?= urlencode($filter) ?>" 
                                   class="list-group-item list-group-item-action <?= $logFile === $selectedLog ? 'active' : '' ?>">
                                    <?= htmlspecialchars($logFile) ?>
                                </a>
                            <?php endforeach; ?>
                            
                            <?php if (empty($logFiles)): ?>
                                <div class="list-group-item">Логи не найдены</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Форма фильтрации -->
                <div class="card mt-3">
                    <div class="card-header">Фильтры</div>
                    <div class="card-body">
                        <form method="get" action="">
                            <input type="hidden" name="log" value="<?= htmlspecialchars($selectedLog) ?>">
                            
                            <div class="mb-3">
                                <label for="filter" class="form-label">Ключевое слово</label>
                                <input type="text" class="form-control" id="filter" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="lines" class="form-label">Количество строк</label>
                                <select class="form-select" id="lines" name="lines">
                                    <option value="50" <?= $maxLines === 50 ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= $maxLines === 100 ? 'selected' : '' ?>>100</option>
                                    <option value="200" <?= $maxLines === 200 ? 'selected' : '' ?>>200</option>
                                    <option value="500" <?= $maxLines === 500 ? 'selected' : '' ?>>500</option>
                                    <option value="1000" <?= $maxLines === 1000 ? 'selected' : '' ?>>1000</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Применить</button>
                            <a href="?log=<?= urlencode($selectedLog) ?>" class="btn btn-secondary">Сбросить</a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Содержимое лога -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Содержимое: <?= htmlspecialchars($selectedLog ?: 'Лог не выбран') ?></span>
                        <?php if ($selectedLog): ?>
                            <a href="?log=<?= urlencode($selectedLog) ?>&lines=<?= $maxLines ?>&filter=<?= urlencode($filter) ?>" class="btn btn-sm btn-outline-secondary">Обновить</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($logContent): ?>
                            <pre><?= highlightLog(htmlspecialchars($logContent)) ?></pre>
                        <?php else: ?>
                            <div class="alert alert-info">Лог пуст или не выбран</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
