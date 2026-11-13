<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo function_exists('csrf_token') ? csrf_token() : ''; ?>">
    <title>Hệ Thống Đặt Sân Bóng Đá Phủi Đà Nẵng</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Core -->
    <link rel="stylesheet" href="assets/css/main.css?v=1.0.2">
    <?php 
        $ctrl = $_GET['controller'] ?? 'Home';
        $action = $_GET['action'] ?? 'index';
        if ($ctrl == 'Auth') echo '<link rel="stylesheet" href="assets/css/auth.css?v=1.0.2">';
        if ($ctrl == 'DatSan' || $ctrl == 'Payment') echo '<link rel="stylesheet" href="assets/css/datsan.css?v=1.0.2">';
        if ($ctrl == 'User') echo '<link rel="stylesheet" href="assets/css/user.css?v=1.0.2">';
        if ($ctrl == 'Home' && $action == 'detail') echo '<link rel="stylesheet" href="assets/css/detail.css?v=1.0.2">';
    ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
    <header class="stitch-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <img src="/quanlysanbong/images/logo.jpg" alt="Logo VH15" class="logo-img">
                <div class="logo-text">
                    <h1>Hệ Thống Đặt Sân Bóng Đá Phủi</h1>
                    <p>Nhanh chóng - Tiện lợi - Uy tín</p>
                </div>
            </a>
