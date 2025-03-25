<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromEmail;
    private string $fromName;
    private bool $debug;
    
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
        $this->debug = (bool)($_ENV['MAIL_DEBUG'] ?? false);
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
            // Создаем экземпляр PHPMailer
            $mail = new PHPMailer(true);
            
            // Включаем отладку если нужно
            if ($this->debug) {
                $mail->SMTPDebug = 2; // Подробный вывод для отладки
            }
            
            // Проверяем, используем ли мы SMTP или локальную отправку
            if (strtolower($_ENV['MAIL_MAILER'] ?? '') === 'smtp') {
                // Настройка SMTP сервера
                $mail->isSMTP();
                $mail->Host = $this->host;
                
                // Настройка аутентификации только если указаны учетные данные
                if (!empty($this->username) && !empty($this->password)) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $this->username;
                    $mail->Password = $this->password;
                } else {
                    $mail->SMTPAuth = false;
                }
                
                $mail->Port = $this->port;
                
                // Настройка шифрования, если указано
                if (!empty($this->encryption)) {
                    if (strtolower($this->encryption) === 'tls') {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    } elseif (strtolower($this->encryption) === 'ssl') {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    }
                } else {
                    // Если шифрование не указано, явно отключаем его
                    $mail->SMTPSecure = false;
                    $mail->SMTPAutoTLS = false;
                }
            } else {
                // Используем встроенную функцию mail() для отправки
                $mail->isMail();
            }
            
            // Настройка отправителя и получателя
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            
            // Установка кодировки
            $mail->CharSet = 'UTF-8';
            
            // Тема и содержимое письма
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Добавление вложений, если есть
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $mail->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? basename($attachment['path'])
                    );
                }
            }
            
            // Отправка письма
            $mail->send();
            
            Application::$app->logger->info(
                "Email sent successfully", 
                ['to' => $to],
                'emails.log'
            );
            return true;
        } catch (Exception $e) {
            Application::$app->logger->error(
                "Failed to send email", 
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
                    <p>Ссылка действительна в течение 24 часов.</p>
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
