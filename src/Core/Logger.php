<?php

namespace App\Core;

class Logger {
    private string $logDir;
    private string $defaultLogFile;
    private bool $enabled = true;
    private array $logLevels = [
        'debug' => 100,
        'info' => 200,
        'notice' => 300,
        'warning' => 400,
        'error' => 500,
        'critical' => 600,
        'alert' => 700,
        'emergency' => 800
    ];
    private int $minLogLevel = 100; // По умолчанию логируем все уровни

    /**
     * Конструктор класса Logger
     * 
     * @param string $logDir Директория для хранения логов
     * @param string $defaultLogFile Имя файла логов по умолчанию
     * @param bool $enabled Включено ли логирование
     * @param string $minLevel Минимальный уровень логирования (debug, info, warning, error, critical, alert, emergency)
     */
    public function __construct(
        string $logDir = null, 
        string $defaultLogFile = 'app.log',
        bool $enabled = true,
        string $minLevel = 'debug'
    ) {
        $this->logDir = $logDir ?? dirname(dirname(__DIR__)) . '/logs';
        $this->defaultLogFile = $defaultLogFile;
        $this->enabled = $enabled;
        
        // Устанавливаем минимальный уровень логирования
        if (isset($this->logLevels[$minLevel])) {
            $this->minLogLevel = $this->logLevels[$minLevel];
        }
        
        // Создаем директорию для логов, если она не существует
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    /**
     * Логирование отладочной информации
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @return bool Результат операции логирования
     */
    public function debug(string $message, array $context = [], string $logFile = null): bool {
        return $this->log('debug', $message, $context, $logFile);
    }

    /**
     * Логирование информационных сообщений
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @return bool Результат операции логирования
     */
    public function info(string $message, array $context = [], string $logFile = null): bool {
        return $this->log('info', $message, $context, $logFile);
    }

    /**
     * Логирование предупреждений
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @return bool Результат операции логирования
     */
    public function warning(string $message, array $context = [], string $logFile = null): bool {
        return $this->log('warning', $message, $context, $logFile);
    }

    /**
     * Логирование ошибок
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @return bool Результат операции логирования
     */
    public function error(string $message, array $context = [], string $logFile = null): bool {
        return $this->log('error', $message, $context, $logFile);
    }

    /**
     * Логирование критических ошибок
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @return bool Результат операции логирования
     */
    public function critical(string $message, array $context = [], string $logFile = null): bool {
        return $this->log('critical', $message, $context, $logFile);
    }

    /**
     * Основной метод логирования
     * 
     * @param string $level Уровень логирования
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @return bool Результат операции логирования
     */
    public function log(string $level, string $message, array $context = [], string $logFile = null): bool {
        if (!$this->enabled) {
            return false;
        }
        
        // Проверяем, соответствует ли уровень логирования минимальному
        if (!isset($this->logLevels[$level]) || $this->logLevels[$level] < $this->minLogLevel) {
            return false;
        }
        
        $logFile = $logFile ?? $this->defaultLogFile;
        $logPath = $this->logDir . '/' . $logFile;
        
        // Форматируем сообщение
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Форматируем контекст
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Получаем информацию о вызывающем коде
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($backtrace[2]) ? $backtrace[2] : $backtrace[1] ?? $backtrace[0] ?? null;
        
        $callerInfo = '';
        if ($caller) {
            $class = $caller['class'] ?? '';
            $type = $caller['type'] ?? '';
            $function = $caller['function'] ?? '';
            $file = isset($caller['file']) ? basename($caller['file']) : '';
            $line = $caller['line'] ?? '';
            
            if ($file && $line) {
                $callerInfo = "[$file:$line]";
            }
            
            if ($class && $function) {
                $callerInfo .= " $class$type$function()";
            } elseif ($function) {
                $callerInfo .= " $function()";
            }
        }
        
        // Формируем строку лога
        $logEntry = "[$timestamp] [$levelUpper]$callerInfo: $message$contextStr" . PHP_EOL;
        
        // Записываем в файл
        $result = file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);
        
        return $result !== false;
    }

    /**
     * Создает новый файл логов с заданным именем
     * 
     * @param string $logFile Имя файла логов
     * @return bool Результат операции
     */
    public function createLogFile(string $logFile): bool {
        $logPath = $this->logDir . '/' . $logFile;
        $result = file_put_contents($logPath, '', LOCK_EX);
        return $result !== false;
    }

    /**
     * Очищает файл логов
     * 
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @return bool Результат операции
     */
    public function clearLog(string $logFile = null): bool {
        $logFile = $logFile ?? $this->defaultLogFile;
        $logPath = $this->logDir . '/' . $logFile;
        $result = file_put_contents($logPath, '', LOCK_EX);
        return $result !== false;
    }

    /**
     * Включает логирование
     */
    public function enable(): void {
        $this->enabled = true;
    }

    /**
     * Отключает логирование
     */
    public function disable(): void {
        $this->enabled = false;
    }

    /**
     * Устанавливает минимальный уровень логирования
     * 
     * @param string $level Уровень логирования (debug, info, warning, error, critical, alert, emergency)
     * @return bool Результат операции
     */
    public function setMinLogLevel(string $level): bool {
        if (isset($this->logLevels[$level])) {
            $this->minLogLevel = $this->logLevels[$level];
            return true;
        }
        return false;
    }

    /**
     * Возвращает текущий минимальный уровень логирования
     * 
     * @return string Текущий минимальный уровень логирования
     */
    public function getMinLogLevel(): string {
        $currentLevel = array_search($this->minLogLevel, $this->logLevels);
        return $currentLevel !== false ? $currentLevel : 'debug';
    }

    /**
     * Получает содержимое файла логов
     * 
     * @param string $logFile Имя файла логов (если отличается от значения по умолчанию)
     * @param int $maxLines Максимальное количество строк для чтения (0 - все строки)
     * @param bool $reverse Читать с конца файла (последние записи первыми)
     * @return array Массив строк из файла логов
     */
    public function getLogContent(string $logFile = null, int $maxLines = 0, bool $reverse = true): array {
        $logFile = $logFile ?? $this->defaultLogFile;
        $logPath = $this->logDir . '/' . $logFile;
        
        if (!file_exists($logPath)) {
            return [];
        }
        
        $content = file($logPath, FILE_IGNORE_NEW_LINES);
        
        if ($reverse) {
            $content = array_reverse($content);
        }
        
        if ($maxLines > 0 && count($content) > $maxLines) {
            $content = array_slice($content, 0, $maxLines);
        }
        
        return $content;
    }

    /**
     * Получает список файлов логов
     * 
     * @return array Список файлов логов
     */
    public function getLogFiles(): array {
        $files = glob($this->logDir . '/*.log');
        $result = [];
        
        foreach ($files as $file) {
            $result[] = basename($file);
        }
        
        return $result;
    }
}
