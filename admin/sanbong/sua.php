<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php';

require_admin();

$error = $success = "";
$san_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($san_id <= 0) {
    echo "<div class='page-header'><h1 class='page-title'>ID sân không hợp lệ.</h1></div>";
    include '../footer_admin.php';
    exit();
}

$stmt_get = $conn->prepare("SELECT * FROM san_bong WHERE san_id = ?");
$stmt_get->bind_param("i", $san_id);
$stmt_get->execute();
$san = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$san) {
    echo "<div class='page-header'><h1 class='page-title'>Không tìm thấy sân bóng.</h1></div>";
    include '../footer_admin.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten_san = trim($_POST['ten_san']);
    $dia_chi = trim($_POST['dia_chi']);
    $so_luong = intval($_POST['so_luong']);
    $loai_san = $_POST['loai_san'] ?? 'san_5';
    $quy_tac_ghep = intval($_POST['quy_tac_ghep'] ?? 0);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);

    if (empty($ten_san) || empty($dia_chi) || $so_luong <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc.";
    } else {
        $lat_val = empty($latitude) ? NULL : $latitude;
        $lng_val = empty($longitude) ? NULL : $longitude;

        $stmt_update = $conn->prepare("UPDATE san_bong SET ten_san = ?, dia_chi = ?, so_luong = ?, latitude = ?, longitude = ?, loai_san = ?, quy_tac_ghep = ? WHERE san_id = ?");
        $stmt_update->bind_param("ssisssii", $ten_san, $dia_chi, $so_luong, $lat_val, $lng_val, $loai_san, $quy_tac_ghep, $san_id);
        $stmt_update->execute();
        
        if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
            $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["hinh_anh"]["tmp_name"]);
            finfo_close($finfo);

            if (in_array($mime, $allowed_mime) && $_FILES['hinh_anh']['size'] <= 5 * 1024 * 1024) {
                $target_dir = "../../images/san_bong/";
                $hinh_anh_ten_moi = $san_id . '.jpg';
                $target_file = $target_dir . $hinh_anh_ten_moi;
                move_uploaded_file($_FILES["hinh_anh"]["tmp_name"], $target_file);
            } else {
                $error = "Cập nhật thông tin thành công, nhưng file ảnh không hợp lệ (chỉ chấp nhận JPG, PNG, WEBP dưới 5MB).";
            }
        }
        
        if (empty($error)) {
            $success = "Cập nhật thông tin sân bóng thành công!";
        }
        
        $stmt_reload = $conn->prepare("SELECT * FROM san_bong WHERE san_id = ?");
        $stmt_reload->bind_param("i", $san_id);
        $stmt_reload->execute();
        $san = $stmt_reload->get_result()->fetch_assoc();
    }
}

$server_image_path = $_SERVER['DOCUMENT_ROOT'] . "/quanlysanbong/images/san_bong/" . $san['san_id'] . '.jpg';
$browser_image_path = "/quanlysanbong/images/san_bong/" . $san['san_id'] . '.jpg';
$img_src = file_exists($server_image_path) ? ($browser_image_path . '?v=' . time()) : '/quanlysanbong/images/placeholder.png';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Sửa Thông Tin Sân Bóng</h1>
        <p class="page-subtitle">Cập nhật cấu hình và hình ảnh cho: <strong><?php echo htmlspecialchars($san['ten_san']); ?></strong></p>
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

<div class="admin-form-card" style="max-width: 920px; margin: 0 auto; padding: 32px 36px;">
    <form method="POST" action="sua.php?id=<?php echo $san_id; ?>" enctype="multipart/form-data">
        <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
        
        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Tên sân bóng (*)</label>
                <input type="text" id="ten_san" name="ten_san" class="form-control" value="<?php echo htmlspecialchars($san['ten_san']); ?>" required>
            </div>
            <div class="form-col">
                <label class="form-label">Loại hình sân (*)</label>
                <select id="loai_san" name="loai_san" class="form-control" onchange="toggleQuyTacGhep(this.value)">
                    <option value="san_5" <?php echo ($san['loai_san'] == 'san_5') ? 'selected' : ''; ?>>Sân 5 (Chỉ cho thuê sân 5)</option>
                    <option value="san_7" <?php echo ($san['loai_san'] == 'san_7') ? 'selected' : ''; ?>>Sân 7 (Chỉ cho thuê sân 7)</option>
                    <option value="san_5_7" <?php echo ($san['loai_san'] == 'san_5_7') ? 'selected' : ''; ?>>Sân 5 &amp; 7 (Ghép linh hoạt)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Địa chỉ sân (*)</label>
            <input type="text" id="dia_chi" name="dia_chi" class="form-control" value="<?php echo htmlspecialchars($san['dia_chi']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Vĩ độ (Latitude)</label>
                <input type="text" id="latitude" name="latitude" class="form-control" value="<?php echo htmlspecialchars($san['latitude'] ?? ''); ?>" placeholder="Ví dụ: 16.054407">
            </div>
            <div class="form-col">
                <label class="form-label">Kinh độ (Longitude)</label>
                <input type="text" id="longitude" name="longitude" class="form-control" value="<?php echo htmlspecialchars($san['longitude'] ?? ''); ?>" placeholder="Ví dụ: 108.202167">
            </div>
        </div>

        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Số lượng sân con (*)</label>
                <input type="number" id="so_luong" name="so_luong" class="form-control" value="<?php echo $san['so_luong']; ?>" min="1" required>
            </div>
            <div class="form-col" id="quyTacGhepBox" style="display: <?php echo ($san['loai_san'] == 'san_5_7') ? 'block' : 'none'; ?>;">
                <label class="form-label">Quy tắc ghép sân 7</label>
                <select id="quy_tac_ghep" name="quy_tac_ghep" class="form-control">
                    <option value="3" <?php echo ($san['quy_tac_ghep'] == 3) ? 'selected' : ''; ?>>3 Sân 5 = 1 Sân 7 (Ghép A+B+C)</option>
                    <option value="2" <?php echo ($san['quy_tac_ghep'] == 2) ? 'selected' : ''; ?>>2 Sân 5 = 1 Sân 7 (Ghép A+B)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Hình ảnh sân hiện tại</label>
            <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 10px;">
                <img src="<?php echo $img_src; ?>" alt="Ảnh sân" style="width: 120px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                <div>
                    <input type="file" id="hinh_anh" name="hinh_anh" class="form-control" accept="image/*">
                    <span style="font-size: 0.78rem; color: var(--text-muted);">Chọn ảnh mới nếu muốn thay đổi ảnh đại diện.</span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn-primary-modern">
                Cập nhật thông tin sân
            </button>
            <a href="index.php" class="btn-outline-modern">
                Hủy bỏ
            </a>
        </div>
    </form>
</div>

<?php include '../footer_admin.php'; ?>
