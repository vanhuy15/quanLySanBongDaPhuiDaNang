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
    }
}

header("Location: index.php");
exit();
