<?php
namespace Core;

class Logger {
    private static string $logDir = __DIR__ . '/../logs';

    /**
     * Ghi log với level tùy biến
     */
    public static function log(string $level, string $message, array $context = []): void {
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }

        $date = date('Y-m-d');
        $logFile = self::$logDir . "/app-{$date}.log";

        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userId = $_SESSION['nguoi_dung_id'] ?? 'Guest';

        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[{$timestamp}] [{$level}] [User:{$userId}] [IP:{$ip}] {$message}{$contextStr}" . PHP_EOL;

        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }

    public static function security(string $message, array $context = []): void {
        self::log('SECURITY', $message, $context);
    }
}

