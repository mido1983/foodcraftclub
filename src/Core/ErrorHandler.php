<?php

namespace App\Core;

/**
 * Класс для обработки ошибок в приложении
 */
class ErrorHandler {
    private Logger $logger;
    private bool $displayErrors;
    
    /**
     * Конструктор класса ErrorHandler
     * 
     * @param Logger $logger Экземпляр логгера
     * @param bool $displayErrors Отображать ли подробные сообщения об ошибках
     */
    public function __construct(Logger $logger, bool $displayErrors = false) {
        $this->logger = $logger;
        $this->displayErrors = $displayErrors;
        
        // Устанавливаем обработчики ошибок и исключений
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
    }
    
    /**
     * Обработчик ошибок PHP
     * 
     * @param int $errno Уровень ошибки
     * @param string $errstr Сообщение об ошибке
     * @param string $errfile Файл, в котором произошла ошибка
     * @param int $errline Строка, в которой произошла ошибка
     * @return bool
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        // Игнорируем ошибки, отключенные в error_reporting
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        // Определяем тип ошибки
        $errorType = $this->getErrorType($errno);
        
        // Формируем сообщение об ошибке
        $message = "$errorType: $errstr в файле $errfile на строке $errline";
        
        // Логируем ошибку
        $this->logError($errorType, $message, [
            'file' => $errfile,
            'line' => $errline,
            'error_code' => $errno
        ]);
        
        // Если это фатальная ошибка, завершаем выполнение скрипта
        if ($errno == E_ERROR || $errno == E_CORE_ERROR || $errno == E_COMPILE_ERROR || $errno == E_USER_ERROR) {
            $this->displayError($message);
            exit(1);
        }
        
        return true;
    }
    
    /**
     * Обработчик исключений
     * 
     * @param \Throwable $exception Исключение
     */
    public function handleException(\Throwable $exception): void {
        // Формируем сообщение об ошибке
        $message = get_class($exception) . ': ' . $exception->getMessage() . 
                   ' в файле ' . $exception->getFile() . ' на строке ' . $exception->getLine();
        
        // Логируем исключение
        $this->logError('Exception', $message, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Отображаем сообщение об ошибке
        $this->displayException($exception);
    }
    
    /**
     * Обработчик фатальных ошибок
     */
    public function handleFatalError(): void {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Формируем сообщение об ошибке
            $errorType = $this->getErrorType($error['type']);
            $message = "$errorType: {$error['message']} в файле {$error['file']} на строке {$error['line']}";
            
            // Логируем фатальную ошибку
            $this->logError('Fatal Error', $message, [
                'file' => $error['file'],
                'line' => $error['line'],
                'error_code' => $error['type']
            ]);
            
            // Отображаем сообщение об ошибке
            $this->displayError($message);
        }
    }
    
    /**
     * Логирует ошибку
     * 
     * @param string $type Тип ошибки
     * @param string $message Сообщение об ошибке
     * @param array $context Контекст ошибки
     */
    private function logError(string $type, string $message, array $context = []): void {
        // Определяем уровень логирования в зависимости от типа ошибки
        switch ($type) {
            case 'Fatal Error':
            case 'Error':
            case 'Parse Error':
            case 'Exception':
                $this->logger->error($message, $context, 'errors.log');
                break;
            case 'Warning':
                $this->logger->warning($message, $context, 'errors.log');
                break;
            case 'Notice':
            case 'Deprecated':
                $this->logger->info($message, $context, 'errors.log');
                break;
            default:
                $this->logger->debug($message, $context, 'errors.log');
        }
    }
    
    /**
     * Отображает сообщение об ошибке
     * 
     * @param string $message Сообщение об ошибке
     */
    private function displayError(string $message): void {
        if ($this->displayErrors) {
            echo '<div style="padding: 10px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">';
            echo '<h3>Произошла ошибка</h3>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            echo '</div>';
        } else {
            // В продакшн-режиме показываем общее сообщение об ошибке
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            
            // Если это AJAX-запрос, возвращаем JSON с ошибкой
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Произошла внутренняя ошибка сервера. Пожалуйста, попробуйте позже.']);
            } else {
                // Перенаправляем на страницу с ошибкой
                if (class_exists('\App\Core\Application') && Application::$app !== null) {
                    try {
                        echo Application::$app->view->renderView('_error', [
                            'exception' => new \Exception('Произошла внутренняя ошибка сервера. Пожалуйста, попробуйте позже.')
                        ]);
                    } catch (\Exception $e) {
                        // Если не удалось отобразить шаблон ошибки, показываем простое сообщение
                        echo '<h1>Ошибка</h1><p>Произошла внутренняя ошибка сервера. Пожалуйста, попробуйте позже.</p>';
                    }
                } else {
                    echo '<h1>Ошибка</h1><p>Произошла внутренняя ошибка сервера. Пожалуйста, попробуйте позже.</p>';
                }
            }
        }
    }
    
    /**
     * Отображает информацию об исключении
     * 
     * @param \Throwable $exception Исключение
     */
    private function displayException(\Throwable $exception): void {
        if ($this->displayErrors) {
            echo '<div style="padding: 10px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">';
            echo '<h3>Произошло исключение: ' . get_class($exception) . '</h3>';
            echo '<p><strong>Сообщение:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
            echo '<p><strong>Файл:</strong> ' . htmlspecialchars($exception->getFile()) . ' (' . $exception->getLine() . ')</p>';
            echo '<h4>Стек вызовов:</h4>';
            echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
            echo '</div>';
        } else {
            // В продакшн-режиме показываем общее сообщение об ошибке
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            
            // Если это AJAX-запрос, возвращаем JSON с ошибкой
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Произошла внутренняя ошибка сервера. Пожалуйста, попробуйте позже.']);
            } else {
                // Перенаправляем на страницу с ошибкой
                if (class_exists('\App\Core\Application') && Application::$app !== null) {
                    try {
                        echo Application::$app->view->renderView('_error', ['exception' => $exception]);
                    } catch (\Exception $e) {
                        // Если не удалось отобразить шаблон ошибки, показываем простое сообщение
                        echo '<h1>Ошибка</h1><p>Произошла внутренняя ошибка сервера. Пожалуйста, попробуйте позже.</p>';
                    }
                } else {
                    echo '<h1>Ошибка</h1><p>Произошла внутренняя ошибка сервера. Пожалуйста, попробуйте позже.</p>';
                }
            }
        }
    }
    
    /**
     * Возвращает текстовое представление типа ошибки
     * 
     * @param int $errorCode Код ошибки
     * @return string
     */
    private function getErrorType(int $errorCode): string {
        switch ($errorCode) {
            case E_ERROR:
                return 'Fatal Error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
                return 'Notice';
            case E_CORE_ERROR:
                return 'Core Error';
            case E_CORE_WARNING:
                return 'Core Warning';
            case E_COMPILE_ERROR:
                return 'Compile Error';
            case E_COMPILE_WARNING:
                return 'Compile Warning';
            case E_USER_ERROR:
                return 'User Error';
            case E_USER_WARNING:
                return 'User Warning';
            case E_USER_NOTICE:
                return 'User Notice';
            case E_STRICT:
                return 'Strict';
            case E_RECOVERABLE_ERROR:
                return 'Recoverable Error';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_DEPRECATED:
                return 'User Deprecated';
            default:
                return 'Unknown Error';
        }
    }
}
