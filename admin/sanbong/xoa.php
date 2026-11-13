<?php
session_start();
include '../../includes/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $san_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($san_id > 0) {
        $image_path = "../../images/san_bong/" . $san_id . '.jpg';
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
        
        \Models\SanBong::delete($san_id);
    }
}

header("Location: index.php");
exit();
