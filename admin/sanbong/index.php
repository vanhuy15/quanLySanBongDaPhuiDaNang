<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php';

require_admin();

$error = $success = "";

// Xử lý khi thêm sân mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_san') {
    $ten_san = trim($_POST['ten_san']);
    $dia_chi = trim($_POST['dia_chi']);
    $so_luong = intval($_POST['so_luong']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $loai_san = $_POST['loai_san'] ?? 'san_5';
    $quy_tac_ghep = intval($_POST['quy_tac_ghep'] ?? 0);

    if (empty($ten_san) || empty($dia_chi) || $so_luong <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc.";
    } else {
        $lat_val = empty($latitude) ? NULL : $latitude;
        $lng_val = empty($longitude) ? NULL : $longitude;

        $stmt_insert = $conn->prepare("INSERT INTO san_bong (ten_san, dia_chi, so_luong, latitude, longitude, loai_san, quy_tac_ghep) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("ssisssi", $ten_san, $dia_chi, $so_luong, $lat_val, $lng_val, $loai_san, $quy_tac_ghep);
        if ($stmt_insert->execute()) {
            $last_id = $conn->insert_id;
            
            if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
                $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES["hinh_anh"]["tmp_name"]);
                finfo_close($finfo);

                if (in_array($mime, $allowed_mime) && $_FILES['hinh_anh']['size'] <= 5 * 1024 * 1024) {
                    $target_dir = "../../images/san_bong/";
                    $hinh_anh_ten_moi = $last_id . '.jpg';
                    $target_file = $target_dir . $hinh_anh_ten_moi;

                    if (move_uploaded_file($_FILES["hinh_anh"]["tmp_name"], $target_file)) {
                        $success = "Thêm sân và tải ảnh đại diện thành công!";
                    } else {
                        $error = "Thêm thông tin sân thành công, nhưng có lỗi khi lưu file ảnh.";
                    }
                } else {
                    $error = "Thêm thông tin thành công, nhưng file ảnh không hợp lệ (chỉ chấp nhận JPG, PNG, WEBP dưới 5MB).";
                }
            } else {
                $success = "Thêm sân bóng mới thành công!";
            }
        } else {
            \Core\Logger::error("Lỗi khi thêm sân bóng: " . $stmt_insert->error);
            $error = "Có lỗi xảy ra khi lưu thông tin sân bóng. Vui lòng kiểm tra lại.";
        }
        $stmt_insert->close();
    }
}

$result = $conn->query("SELECT * FROM san_bong ORDER BY san_id ASC");
$total_pitches = $result ? $result->num_rows : 0;
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Danh sách Sân bóng</h1>
        <p class="page-subtitle">Quản lý trạng thái và thông tin chi tiết các sân hiện tại.</p>
    </div>
    
    <div class="page-actions">
        <button type="button" class="btn-primary-modern" onclick="toggleAddForm()">
            Thêm sân mới
        </button>
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

<!-- FORM THÊM SÂN MỚI (COLLAPSIBLE) -->
<div id="addPitchFormContainer" style="display: <?php echo (!empty($error)) ? 'block' : 'none'; ?>; margin-bottom: 24px;">
    <div class="admin-form-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-heading);">
                Thêm Sân Bóng Mới
            </h3>
            <button type="button" onclick="toggleAddForm()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="index.php" enctype="multipart/form-data">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
            <input type="hidden" name="action" value="add_san">
            
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Tên sân bóng (*)</label>
                    <input type="text" id="ten_san" name="ten_san" class="form-control" required placeholder="Ví dụ: Sân bóng Mini Mikazuki">
                </div>
                <div class="form-col">
                    <label class="form-label">Loại hình sân (*)</label>
                    <select id="loai_san" name="loai_san" class="form-control" onchange="toggleQuyTacGhep(this.value)">
                        <option value="san_5">Sân 5 (Chỉ cho thuê sân 5)</option>
                        <option value="san_7">Sân 7 (Chỉ cho thuê sân 7)</option>
                        <option value="san_5_7">Sân 5 &amp; 7 (Ghép linh hoạt)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Địa chỉ sân (*)</label>
                <input type="text" id="dia_chi" name="dia_chi" class="form-control" required placeholder="Ví dụ: 123 Nguyễn Tất Thành, Đà Nẵng">
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Vĩ độ (Latitude)</label>
                    <input type="text" id="latitude" name="latitude" class="form-control" placeholder="Ví dụ: 16.054407">
                </div>
                <div class="form-col">
                    <label class="form-label">Kinh độ (Longitude)</label>
                    <input type="text" id="longitude" name="longitude" class="form-control" placeholder="Ví dụ: 108.202167">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Số lượng sân con (*)</label>
                    <input type="number" id="so_luong" name="so_luong" class="form-control" value="3" min="1" required>
                </div>
                <div class="form-col" id="quyTacGhepBox" style="display: none;">
                    <label class="form-label">Quy tắc ghép sân 7</label>
                    <select id="quy_tac_ghep" name="quy_tac_ghep" class="form-control">
                        <option value="3">3 Sân 5 = 1 Sân 7 (Ghép A+B+C)</option>
                        <option value="2">2 Sân 5 = 1 Sân 7 (Ghép A+B)</option>
                    </select>
                </div>
                <div class="form-col">
                    <label class="form-label">Hình ảnh sân bóng</label>
                    <input type="file" id="hinh_anh" name="hinh_anh" class="form-control" accept="image/*">
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 14px;">
                <button type="submit" class="btn-primary-modern">
                    Lưu sân mới
                </button>
                <button type="button" class="btn-outline-modern" onclick="toggleAddForm()">
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<!-- PITCH CARD GRID (STITCH IMAGE 3 STYLE) -->
<div class="pitch-grid">
    <?php if ($result && $result->num_rows > 0) : ?>
        <?php while ($row = $result->fetch_assoc()) : 
            $server_image_path = $_SERVER['DOCUMENT_ROOT'] . "/quanlysanbong/images/san_bong/" . $row['san_id'] . '.jpg';
            $browser_image_path = "/quanlysanbong/images/san_bong/" . $row['san_id'] . '.jpg';
            $img_src = file_exists($server_image_path) ? ($browser_image_path . '?v=' . time()) : '/quanlysanbong/images/placeholder.png';
            
            $loai_txt = 'Sân 5';
            if ($row['loai_san'] == 'san_7') $loai_txt = 'Sân 7';
            if ($row['loai_san'] == 'san_5_7') $loai_txt = 'Sân 5 & 7';
        ?>
        <div class="pitch-card">
            <div class="pitch-card-img-wrap">
                <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($row['ten_san']); ?>" class="pitch-card-img">
                <span class="pitch-type-tag"><?php echo $loai_txt; ?></span>
            </div>
            
            <div class="pitch-card-body">
                <div class="pitch-card-header" style="justify-content: center; text-align: center;">
                    <h3 class="pitch-name" style="text-align: center; width: 100%;"><?php echo htmlspecialchars($row['ten_san']); ?></h3>
                </div>
                
                <p class="pitch-desc" style="text-align: center;">
                    <?php echo htmlspecialchars($row['dia_chi']); ?>
                </p>
                
                <div class="pitch-card-footer">
                    <span class="pitch-sub-badge">
                        <?php echo $row['so_luong']; ?> sân con
                    </span>
                    
                    <div class="action-buttons-group">
                        <a href="sua.php?id=<?php echo $row['san_id']; ?>" class="btn-action-icon btn-edit" title="Chỉnh sửa thông tin sân">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="xoa.php" style="display:inline;" onsubmit="return confirm('Xác nhận xóa sân bóng này cùng toàn bộ dữ liệu liên quan?');">
                            <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                            <input type="hidden" name="id" value="<?php echo $row['san_id']; ?>">
                            <button type="submit" class="btn-action-icon btn-cancel" title="Xóa sân bóng" style="background:none; border:none; cursor:pointer; padding:0;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else : ?>
        <div style="grid-column: 1 / -1; padding: 60px; text-align: center; background: #ffffff; border-radius: var(--radius-lg); border: 1px solid var(--border); color: var(--text-muted);">
            <i class="fas fa-futbol" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
            <h3>Chưa có sân bóng nào trong hệ thống</h3>
            <p style="margin-top: 6px;">Hãy bấm nút "Thêm sân mới" ở trên để bắt đầu tạo sân.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer_admin.php'; ?>
