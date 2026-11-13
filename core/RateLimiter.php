<?php
namespace Core;

class RateLimiter {
    private static string $storageDir = __DIR__ . '/../storage/ratelimit';

    private static function getFilePath(string $key): string {
        if (!is_dir(self::$storageDir)) {
            @mkdir(self::$storageDir, 0755, true);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key . '_' . $ip);
        return self::$storageDir . '/' . md5($safeKey) . '.json';
    }

    /**
     * Kiểm tra xem IP hiện tại có bị khóa do vượt quá số lần thử không
     */
    public static function isLimited(string $action, int $maxAttempts = 5, int $decaySeconds = 900): bool {
        $file = self::getFilePath($action);
        if (!file_exists($file)) {
            return false;
        }

        $data = json_decode(@file_get_contents($file), true);
        if (!$data || !isset($data['attempts']) || !isset($data['expires_at'])) {
            return false;
        }

        if (time() > $data['expires_at']) {
            @unlink($file);
            return false;
        }

        return $data['attempts'] >= $maxAttempts;
    }

    /**
     * Ghi nhận 1 lần thử sai
     */
    public static function hit(string $action, int $decaySeconds = 900): int {
        $file = self::getFilePath($action);
        $attempts = 1;
        $expiresAt = time() + $decaySeconds;

        if (file_exists($file)) {
            $data = json_decode(@file_get_contents($file), true);
            if ($data && time() <= ($data['expires_at'] ?? 0)) {
                $attempts = ($data['attempts'] ?? 0) + 1;
                $expiresAt = $data['expires_at'];
            }
        }

        $payload = [
            'attempts' => $attempts,
            'expires_at' => $expiresAt,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'action' => $action
        ];

        @file_put_contents($file, json_encode($payload), LOCK_EX);
        return $attempts;
    }

    /**
     * Lấy số giây còn lại cần chờ
     */
    public static function remainingSeconds(string $action): int {
        $file = self::getFilePath($action);
        if (!file_exists($file)) {
            return 0;
        }
        $data = json_decode(@file_get_contents($file), true);
        if (!$data || !isset($data['expires_at'])) {
            return 0;
        }
        return max(0, $data['expires_at'] - time());
    }

    /**
     * Xóa lịch sử thử sai (ví dụ khi đăng nhập thành công)
     */
    public static function clear(string $action): void {
        $file = self::getFilePath($action);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
