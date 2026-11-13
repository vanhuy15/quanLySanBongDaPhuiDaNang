<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php'; 
require_admin();

$error = $success = "";
$khung_gio_id = isset($_GET['khung_gio_id']) ? intval($_GET['khung_gio_id']) : 0;
$kg = null;

if ($khung_gio_id > 0) {
    $stmt = $conn->prepare("SELECT kg.*, sb.ten_san, sb.loai_san FROM khung_gio kg JOIN san_bong sb ON kg.san_id = sb.san_id WHERE kg.khung_gio_id = ?");
    $stmt->bind_param("i", $khung_gio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $kg = $result->fetch_assoc();
    } else {
        $error = "Không tìm thấy khung giờ với ID: $khung_gio_id";
    }
    $stmt->close();
} else {
    $error = "ID khung giờ không hợp lệ.";
}

if ($khung_gio_id > 0 && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $gio_bat_dau = clean_input($_POST['gio_bat_dau']);
    $gio_ket_thuc = clean_input($_POST['gio_ket_thuc']);
    $gia_tien = !empty($_POST['gia_tien']) ? floatval($_POST['gia_tien']) : 0;
    $gia_tien_san_7 = !empty($_POST['gia_tien_san_7']) ? floatval($_POST['gia_tien_san_7']) : NULL;
    
    if (empty($gio_bat_dau) || empty($gio_ket_thuc)) {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    } else {
        $stmt_update = $conn->prepare("UPDATE khung_gio SET gio_bat_dau=?, gio_ket_thuc=?, gia_tien=?, gia_tien_san_7=? WHERE khung_gio_id=?");
        $stmt_update->bind_param("ssddi", $gio_bat_dau, $gio_ket_thuc, $gia_tien, $gia_tien_san_7, $khung_gio_id);
        
        if ($stmt_update->execute()) {
            $success = "Cập nhật khung giờ thành công!";
            $stmt_refresh = $conn->prepare("SELECT kg.*, sb.ten_san, sb.loai_san FROM khung_gio kg JOIN san_bong sb ON kg.san_id = sb.san_id WHERE kg.khung_gio_id = ?");
            $stmt_refresh->bind_param("i", $khung_gio_id);
            $stmt_refresh->execute();
            $kg = $stmt_refresh->get_result()->fetch_assoc();
            $stmt_refresh->close();
        } else {
            $error = "Lỗi CSDL: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sửa Khung Giờ &amp; Bảng Giá</h1>
        <p class="page-subtitle">Sân bóng: <strong><?php echo htmlspecialchars($kg['ten_san'] ?? ''); ?></strong></p>
    </div>
    <div class="page-actions">
        <a href="index.php?san_id=<?php echo $kg['san_id'] ?? 0; ?>" class="btn-outline-modern">
            Quay lại danh sách
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 20px; color: #047857; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i>
        <span><?php echo htmlspecialchars($success); ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 20px; color: #b91c1c; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if ($kg): ?>
    <div class="admin-form-card" style="max-width: 680px; margin: 0 auto;">
        <form method="POST" action="sua.php?khung_gio_id=<?php echo $khung_gio_id; ?>">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
            
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Giờ bắt đầu</label>
                    <input type="time" name="gio_bat_dau" id="gio_bat_dau" class="form-control" value="<?php echo date('H:i', strtotime($kg['gio_bat_dau'])); ?>" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Giờ kết thúc</label>
                    <input type="time" name="gio_ket_thuc" id="gio_ket_thuc" class="form-control" value="<?php echo date('H:i', strtotime($kg['gio_ket_thuc'])); ?>" required>
                </div>
            </div>

            <?php
                $is_san_5 = ($kg['loai_san'] == 'san_5');
                $is_san_7 = ($kg['loai_san'] == 'san_7');
                
                $req_san_5 = ($is_san_7) ? "disabled readonly style='background-color: #f1f5f9; cursor: not-allowed; opacity: 0.6;'" : "required";
                $req_san_7 = ($is_san_5) ? "disabled readonly style='background-color: #f1f5f9; cursor: not-allowed; opacity: 0.6;'" : "";
                if ($kg['loai_san'] == 'san_7') {
                    $req_san_7 = "required";
                }
            ?>
            <div class="form-group">
                <label class="form-label">Giá Sân 5 (VND) <?php echo $is_san_7 ? '' : '<span style="color:#ef4444;">*</span>'; ?></label>
                <input type="number" name="gia_tien" value="<?php echo intval($kg['gia_tien']); ?>" min="0" step="1000" class="form-control" <?php echo $req_san_5; ?>>
            </div>

            <div class="form-group">
                <label class="form-label">Giá Sân 7 (VND) <?php echo $kg['loai_san'] == 'san_7' ? '<span style="color:#ef4444;">*</span>' : ''; ?></label>
                <input type="number" name="gia_tien_san_7" value="<?php echo !empty($kg['gia_tien_san_7']) ? intval($kg['gia_tien_san_7']) : ''; ?>" min="0" step="1000" class="form-control" <?php echo $req_san_7; ?>>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn-primary-modern">
                    Lưu Thay Đổi
                </button>
                <a href="index.php?san_id=<?php echo $kg['san_id']; ?>" class="btn-outline-modern">
                    Hủy bỏ
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php include '../footer_admin.php'; ?>
