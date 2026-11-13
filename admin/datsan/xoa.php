<?php
session_start();
include '../../includes/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dat_san_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status_return = isset($_POST['status_return']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['status_return']) : '';

    if ($dat_san_id > 0) {
        \Models\DatSan::deleteHard($dat_san_id);
    }

    $redirect_url = "index.php" . (!empty($status_return) ? "?status=" . urlencode($status_return) : "");
    header("Location: " . $redirect_url);
    exit();
}

header("Location: index.php");
exit();
