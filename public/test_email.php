<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Mailer;
use Dotenv\Dotenv;

// Загрузка переменных окружения из .env файла
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Инициализация приложения
$app = new Application(dirname(__DIR__));

// Создание экземпляра класса Mailer
$mailer = new Mailer();

// Email для тестирования (будет перехвачен Mailtrap)
$testEmail = 'test@example.com';

// Отправка тестового письма
$subject = 'Тестовое письмо из Food Craft Club';
$body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тестовое письмо</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
        .content { padding: 20px; }
        .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Food Craft Club</h2>
        </div>
        <div class="content">
            <h1>Тестовое письмо</h1>
            <p>Это тестовое письмо отправлено из приложения Food Craft Club.</p>
            <p>Время отправки: <?= date('Y-m-d H:i:s') ?></p>
        </div>
        <div class="footer">
            <p>С уважением, команда Food Craft Club</p>
        </div>
    </div>
</body>
</html>
HTML;

// Попытка отправки письма
$result = $mailer->send($testEmail, $subject, $body);

// Вывод результата
header('Content-Type: text/html; charset=utf-8');

if ($result) {
    echo "<h1>Письмо успешно отправлено!</h1>";
    echo "<p>Проверьте входящие письма в Mailtrap.</p>";
    echo "<p><a href='https://mailtrap.io/inboxes' target='_blank'>Открыть Mailtrap</a></p>";
} else {
    echo "<h1>Ошибка при отправке письма</h1>";
    echo "<p>Проверьте лог-файл: logs/emails.log</p>";
}

// Вывод последних записей из лога
echo "<h2>Последние записи из лога:</h2>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto;'>";
$logFile = dirname(__DIR__) . '/logs/emails.log';
if (file_exists($logFile)) {
    $logs = file($logFile);
    $lastLogs = array_slice($logs, -10); // Последние 10 записей
    foreach ($lastLogs as $log) {
        echo htmlspecialchars($log);
    }
} else {
    echo "Лог-файл не найден.";
}
echo "</pre>";
