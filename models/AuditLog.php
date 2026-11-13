<?php
namespace Models;

use Config\Database;
use Core\Logger;

class AuditLog {
    /**
     * Khởi tạo bảng audit_logs nếu chưa tồn tại
     */
    public static function initTable(): void {
        $conn = Database::getConnection();
        $sql = "CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `action` VARCHAR(100) NOT NULL,
            `target_type` VARCHAR(50) NOT NULL,
            `target_id` INT NULL,
            `details` TEXT NULL,
            `ip_address` VARCHAR(45) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user_action` (`user_id`, `action`),
            INDEX `idx_target` (`target_type`, `target_id`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $conn->query($sql);
    }

    /**
     * Ghi nhận 1 hành động quản trị viên
     */
    public static function log(?int $userId, string $action, string $targetType, ?int $targetId = null, string $details = ''): bool {
        self::initTable();
        $conn = Database::getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';

        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            Logger::error("Không thể ghi AuditLog: " . $conn->error);
            return false;
        }

        $stmt->bind_param("ississ", $userId, $action, $targetType, $targetId, $details, $ip);
        $result = $stmt->execute();
        $stmt->close();

        // Đồng thời ghi vào structured log
        Logger::info("ADMIN_ACTION [{$action}] on [{$targetType}:{$targetId}] by User [{$userId}]: {$details}");
        return $result;
    }

    /**
     * Lấy danh sách lịch sử thao tác gần nhất
     */
    public static function getLatest(int $limit = 50): array {
        self::initTable();
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT al.*, nd.ten_dang_nhap, nd.ho_ten 
            FROM audit_logs al
            LEFT JOIN nguoi_dung nd ON al.user_id = nd.nguoi_dung_id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $logs = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $logs;
    }
}
