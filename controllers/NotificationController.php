<?php
namespace Controllers;

use Core\Controller;
use Models\Notification;

class NotificationController extends Controller {

    /**
     * Lấy danh sách thông báo mới nhất qua AJAX
     */
    public function getLatest() {
        if (!isset($_SESSION['nguoi_dung_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Chưa đăng nhập'], 401);
            return;
        }

        $userId = $_SESSION['nguoi_dung_id'];
        $notifications = Notification::getByUser($userId, 10);
        $unreadCount = Notification::getUnreadCount($userId);

        $this->jsonResponse([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
    }

    /**
     * Đánh dấu 1 thông báo là đã đọc
     */
    public function markRead() {
        if (!isset($_SESSION['nguoi_dung_id'])) {
            $this->jsonResponse(['success' => false], 401);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $userId = $_SESSION['nguoi_dung_id'];

        if ($id > 0) {
            Notification::markAsRead($id, $userId);
        }

        $this->jsonResponse([
            'success' => true,
            'unread_count' => Notification::getUnreadCount($userId)
        ]);
    }

    /**
     * Đánh dấu tất cả là đã đọc
     */
    public function markAllRead() {
        if (!isset($_SESSION['nguoi_dung_id'])) {
            $this->jsonResponse(['success' => false], 401);
            return;
        }

        $userId = $_SESSION['nguoi_dung_id'];
        Notification::markAllAsRead($userId);

        $this->jsonResponse([
            'success' => true,
            'unread_count' => 0
        ]);
    }
}
