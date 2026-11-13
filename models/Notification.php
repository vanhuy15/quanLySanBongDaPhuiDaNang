<?php
namespace Models;

use Config\Database;

class Notification {
    
    /**
     * Tạo một thông báo mới cho người dùng
     */
    public static function create($userId, $title, $content, $type = 'info', $link = null) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO thong_bao (nguoi_dung_id, tieu_de, noi_dung, loai, lien_ket) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $title, $content, $type, $link);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Lấy danh sách thông báo của người dùng
     */
    public static function getByUser($userId, $limit = 10) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM thong_bao WHERE nguoi_dung_id = ? ORDER BY ngay_tao DESC LIMIT ?");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) {
            $row['time_ago'] = self::formatTimeAgo($row['ngay_tao']);
            $list[] = $row;
        }
        $stmt->close();
        return $list;
    }

    /**
     * Đếm số lượng thông báo chưa đọc
     */
    public static function getUnreadCount($userId) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM thong_bao WHERE nguoi_dung_id = ? AND da_doc = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['unread_count'] ?? 0);
    }

    /**
     * Đánh dấu 1 thông báo là đã đọc
     */
    public static function markAsRead($notificationId, $userId) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE thong_bao SET da_doc = 1 WHERE thong_bao_id = ? AND nguoi_dung_id = ?");
        $stmt->bind_param("ii", $notificationId, $userId);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Đánh dấu tất cả thông báo là đã đọc
     */
    public static function markAllAsRead($userId) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE thong_bao SET da_doc = 1 WHERE nguoi_dung_id = ?");
        $stmt->bind_param("i", $userId);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Helper định dạng thời gian thân thiện (VD: "5 phút trước", "Hôm nay, 14:00")
     */
    private static function formatTimeAgo($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return "Vừa xong";
        } elseif ($diff < 3600) {
            return floor($diff / 60) . " phút trước";
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . " giờ trước";
        } elseif ($diff < 172800) {
            return "Hôm qua lúc " . date('H:i', $time);
        } else {
            return date('d/m/Y H:i', $time);
        }
    }
}
