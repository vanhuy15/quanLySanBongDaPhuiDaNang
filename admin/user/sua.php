<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php';

require_admin();

$error = $success = "";
$nguoi_dung_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($nguoi_dung_id <= 0) {
    echo "<div class='page-header'><h1 class='page-title'>ID người dùng không hợp lệ.</h1></div>";
    include '../footer_admin.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ho_ten = trim($_POST['ho_ten']);
    $sdt = trim($_POST['sdt']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    
    $sql_pass = "";
    $params = [$ho_ten, $sdt, $email];
    $types = "sss";

    if (!preg_match('/^[0-9]{10}$/', $sdt)) {
        $error = "Số điện thoại không hợp lệ! Phải bao gồm đúng 10 chữ số.";
    }

    $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|gov|info|biz|io|co|me|xyz|ai|pro|com\.vn|edu\.vn|gov\.vn|net\.vn|org\.vn)$/i';
    if (empty($error) && (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($emailRegex, $email))) {
        $error = "Email không hợp lệ, vui lòng kiểm tra và thử lại!";
    }

    if (empty($error) && !empty($new_password)) {
        if (strlen($new_password) < 6) {
            $error = "Mật khẩu mới quá ngắn! Phải có ít nhất 6 ký tự.";
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_pass = ", mat_khau = ?";
            $params[] = $hash;
            $types .= "s";
        }
    }

    if (empty($error)) {
        $params[] = $nguoi_dung_id;
        $types .= "i";
        
        $sql = "UPDATE nguoi_dung SET ho_ten=?, sdt=?, email=? $sql_pass WHERE nguoi_dung_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $success = "Cập nhật thông tin khách hàng thành công!";
            if(!empty($new_password)) $success .= " Đã đổi mật khẩu mới.";
        } else {
            $error = "Lỗi: " . $stmt->error;
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE nguoi_dung_id = ?");
$stmt->bind_param("i", $nguoi_dung_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sửa Thông Tin Khách Hàng</h1>
        <p class="page-subtitle">Tài khoản: <strong><?php echo htmlspecialchars($user['ten_dang_nhap'] ?? ''); ?></strong></p>
    </div>
    <div class="page-actions">
        <a href="index.php" class="btn-outline-modern">
            Quay lại danh sách
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 20px; color: #047857; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
        <span><?php echo $success; ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 20px; color: #b91c1c; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if ($user): ?>
    <div class="admin-form-card" style="max-width: 680px; margin: 0 auto;">
        <form method="POST" action="sua.php?id=<?php echo $nguoi_dung_id; ?>">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
            
            <div class="form-group">
                <label class="form-label">Họ và tên (*)</label>
                <input type="text" name="ho_ten" class="form-control" value="<?php echo htmlspecialchars($user['ho_ten']); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Số điện thoại (*)</label>
                    <input type="text" name="sdt" class="form-control" value="<?php echo htmlspecialchars($user['sdt']); ?>" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Email (*)</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: var(--radius-md); padding: 18px; margin: 20px 0;">
                <label class="form-label" style="color: #b45309;">
                    Đặt lại mật khẩu mới cho khách
                </label>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 10px;">
                    Chỉ nhập vào ô dưới đây nếu khách hàng yêu cầu reset mật khẩu. Để trống nếu giữ nguyên.
                </p>
                <input type="text" name="new_password" placeholder="Nhập mật khẩu mới (VD: 123456)" class="form-control" style="background: #ffffff;">
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn-primary-modern">
                    Cập Nhật Thông Tin
                </button>
                <a href="index.php" class="btn-outline-modern">
                    Hủy bỏ
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include '../footer_admin.php'; ?>
