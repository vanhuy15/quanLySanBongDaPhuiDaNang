<?php
session_start();
include '../../includes/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nguoi_dung_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    $allowed_status = ['active', 'banned', 'inactive'];

    if ($nguoi_dung_id > 0 && in_array($status, $allowed_status)) {
        \Models\User::updateStatus($nguoi_dung_id, $status);
        if (class_exists('\\Models\\AuditLog')) {
            \Models\AuditLog::log($_SESSION['nguoi_dung_id'] ?? null, 'ADMIN_CHANGE_USER_STATUS', 'nguoi_dung', $nguoi_dung_id, "Thay đổi trạng thái người dùng thành: {$status}");
        }
    }
}

header("Location: index.php");
exit();
