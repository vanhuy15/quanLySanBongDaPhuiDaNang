<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản lý Sân Bóng</title>
    <link rel="stylesheet" href="assets/css/auth.css?v=1.0.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

    <div class="login-box">
        <div class="auth-logo">
            <img src="/quanlysanbong/images/logo.jpg" alt="Logo VH15" class="auth-logo-img">
        </div>
        <h2>Đăng nhập hệ thống</h2>
        <p class="auth-subtitle">Chào mừng bạn quay trở lại!</p>

        <?php if(isset($success) && $success): ?>
            <div class="success-box" style="background: #ecfdf5; color: #047857; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; border: 1px solid #a7f3d0;">
                <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($error) && $error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?controller=Auth&action=login" id="loginForm">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" id="username" placeholder="Tên đăng nhập" required>
            </div>
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" name="password" id="password" class="has-toggle" placeholder="Mật khẩu" required>
                <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('password', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.15rem; padding: 4px; z-index: 10; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" name="dangnhap" class="btn-auth">Đăng nhập</button>
        </form>
        
        <div class="auth-links">
            <a href="index.php?controller=Auth&action=register">Tạo tài khoản mới</a>
            <a href="index.php?controller=Auth&action=forgotPassword">Quên mật khẩu?</a>
        </div>
        
        <div class="back-home">
            <a href="index.php">Quay lại trang chủ</a>
        </div>
    </div>

<script src="assets/js/auth.js?v=1.0.1"></script>
</body>
</html>

