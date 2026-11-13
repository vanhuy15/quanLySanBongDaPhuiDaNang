<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Quản lý Sân Bóng</title>
    <link rel="stylesheet" href="assets/css/auth.css?v=1.0.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

    <div class="login-box">
        <div class="auth-logo">
            <img src="/quanlysanbong/images/logo.jpg" alt="Logo VH15" class="auth-logo-img">
        </div>
        <h2>Đặt lại mật khẩu</h2>
        <p class="auth-subtitle">Nhập mật khẩu mới cho tài khoản của bạn</p>

        <?php if(isset($error) && $error): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($token)): ?>
        <form method="POST" action="index.php?controller=Auth&action=resetPassword&token=<?php echo htmlspecialchars($token); ?>">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>

            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" name="mat_khau_moi" id="mat_khau_moi" class="has-toggle"
                       placeholder="Mật khẩu mới (từ 6 ký tự)" required minlength="6">
                <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('mat_khau_moi', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.15rem; padding: 4px; z-index: 10; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" name="xac_nhan_mat_khau" id="xac_nhan_mat_khau" class="has-toggle"
                       placeholder="Nhập lại mật khẩu mới" required minlength="6">
                <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('xac_nhan_mat_khau', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.15rem; padding: 4px; z-index: 10; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit" class="btn-auth">Lưu mật khẩu mới</button>
        </form>
        <?php else: ?>
        <div style="text-align: center; padding: 1rem 0;">
            <p style="color: #ef4444; margin-bottom: 1rem;">Link đã hết hạn hoặc không hợp lệ.</p>
            <a href="index.php?controller=Auth&action=forgotPassword" class="btn-auth" style="display: inline-block; text-decoration: none;">
                Gửi lại yêu cầu
            </a>
        </div>
        <?php endif; ?>

        <div class="back-home">
            <a href="index.php?controller=Auth&action=login">Quay lại đăng nhập</a>
        </div>
    </div>

    <script src="assets/js/auth.js?v=1.0.1"></script>
</body>
</html>

