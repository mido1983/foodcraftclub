<?php

namespace App\Core;

class Mailer {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromEmail;
    private string $fromName;
    
    /**
     * Mailer constructor
     */
    public function __construct() {
        // Загрузка конфигурации из файла или переменных окружения
        $this->host = $_ENV['MAIL_HOST'] ?? 'localhost';
        $this->port = (int)($_ENV['MAIL_PORT'] ?? 25);
        $this->username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->encryption = $_ENV['MAIL_ENCRYPTION'] ?? '';
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@foodcraftclub.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Food Craft Club';
    }
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $attachments Optional attachments
     * @return bool Whether the email was sent successfully
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool {
        // Логирование попытки отправки
        Application::$app->logger->info(
            "Attempting to send email", 
            ['to' => $to, 'subject' => $subject],
            'emails.log'
        );
        
        try {
            // Заголовки письма
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Отправка письма с использованием встроенной функции mail
            $success = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($success) {
                Application::$app->logger->info(
                    "Email sent successfully", 
                    ['to' => $to],
                    'emails.log'
                );
                return true;
            } else {
                Application::$app->logger->error(
                    "Failed to send email", 
                    ['to' => $to, 'error' => error_get_last()],
                    'emails.log'
                );
                return false;
            }
        } catch (\Exception $e) {
            Application::$app->logger->error(
                "Exception when sending email", 
                ['to' => $to, 'error' => $e->getMessage()],
                'emails.log'
            );
            return false;
        }
    }
    
    /**
     * Send a password reset email
     * 
     * @param string $to Recipient email
     * @param string $token Reset token
     * @param string $username User's name
     * @return bool Whether the email was sent successfully
     */
    public function sendPasswordResetEmail(string $to, string $token, string $username): bool {
        $subject = 'Сброс пароля на Food Craft Club';
        
        // Формирование URL для сброса пароля
        $resetUrl = Application::$app->request->getBaseUrl() . '/reset-password?token=' . urlencode($token);
        
        // HTML-шаблон письма
        $body = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Сброс пароля</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
                .content { padding: 20px; }
                .button { display: inline-block; background-color: #28a745; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; margin: 20px 0; }
                .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Food Craft Club</h2>
                </div>
                <div class="content">
                    <p>Здравствуйте, {$username}!</p>
                    <p>Мы получили запрос на сброс пароля для вашей учетной записи на сайте Food Craft Club.</p>
                    <p>Для сброса пароля перейдите по ссылке ниже:</p>
                    <p><a href="{$resetUrl}" class="button">Сбросить пароль</a></p>
                    <p>Или скопируйте и вставьте следующую ссылку в адресную строку браузера:</p>
                    <p>{$resetUrl}</p>
                    <p>Если вы не запрашивали сброс пароля, проигнорируйте это письмо.</p>
                    <p>Ссылка действительна в течение 1 часа.</p>
                </div>
                <div class="footer">
                    <p>С уважением, команда Food Craft Club</p>
                    <p>Это автоматическое сообщение, пожалуйста, не отвечайте на него.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
        
        return $this->send($to, $subject, $body);
    }
}
