<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - Quản lý Sân Bóng</title>
    <link rel="stylesheet" href="assets/css/auth.css?v=1.0.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

    <div class="login-box">
        <div class="auth-logo">
            <img src="/quanlysanbong/images/logo.jpg" alt="Logo VH15" class="auth-logo-img">
        </div>
        <h2>Khôi phục mật khẩu</h2>
        <p class="auth-subtitle">Nhập email để nhận hướng dẫn khôi phục</p>

        <?php if(isset($error) && $error): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($success) && $success): ?>
            <div class="success-box"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?controller=Auth&action=forgotPassword">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Nhập địa chỉ Email của bạn" required>
            </div>
            <button type="submit" class="btn-auth">Gửi yêu cầu</button>
        </form>
        
        <div class="back-home">
            <a href="index.php?controller=Auth&action=login">Quay lại đăng nhập</a>
        </div>
    </div>

</body>
</html>

