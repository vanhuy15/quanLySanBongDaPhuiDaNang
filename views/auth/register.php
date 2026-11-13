<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Quản lý Sân Bóng</title>
    <link rel="stylesheet" href="assets/css/auth.css?v=1.0.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

    <div class="register-box">
        <div class="auth-logo">
            <img src="/quanlysanbong/images/logo.jpg" alt="Logo VH15" class="auth-logo-img">
        </div>
        <h2>Tạo tài khoản mới</h2>
        <p class="auth-subtitle">Trải nghiệm đặt sân dễ dàng và nhanh chóng</p>

        <?php if(isset($error) && $error): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if(isset($success) && $success): ?>
            <div class="success-box"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php?controller=Auth&action=register" id="registerForm">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
            <div class="input-row">
                <div class="input-group">
                    <i class="fas fa-id-badge"></i>
                    <input type="text" name="ho_ten" id="ho_ten" placeholder="Họ và tên" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="ten_dang_nhap" id="ten_dang_nhap" placeholder="Tên đăng nhập" required>
                </div>
            </div>
            
            <div class="input-row">
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" name="mat_khau" id="mat_khau" class="has-toggle" placeholder="Mật khẩu (từ 6 ký tự)" required minlength="6">
                    <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('mat_khau', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.15rem; padding: 4px; z-index: 10; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" name="nhap_lai_mat_khau" id="nhap_lai_mat_khau" class="has-toggle" placeholder="Nhập lại mật khẩu" required minlength="6">
                    <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility('nhap_lai_mat_khau', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.15rem; padding: 4px; z-index: 10; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="input-row">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="sdt" id="sdt" placeholder="Số điện thoại" required pattern="[0-9]{10}">
                </div>
            </div>
            
            <button type="submit" name="dangky" class="btn-auth">Đăng ký tài khoản</button>
        </form>
        
        <div class="auth-links">
            <span>Đã có tài khoản?</span> <a href="index.php?controller=Auth&action=login">Đăng nhập ngay</a>
        </div>
        
        <div class="back-home">
            <a href="index.php">Quay lại trang chủ</a>
        </div>
    </div>

<script src="assets/js/auth.js?v=1.0.1"></script>
</body>
</html>

