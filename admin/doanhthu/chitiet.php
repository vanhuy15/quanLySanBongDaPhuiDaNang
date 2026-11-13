<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php';

require_admin();

// 1. LẤY THAM SỐ
$min_date = '2025-01-01';
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if ($filter_date < $min_date) {
    $filter_date = $min_date;
}
$pay_method = isset($_GET['method']) ? $_GET['method'] : 'all';
$date_display = date("d/m/Y", strtotime($filter_date));

// 2. CẤU HÌNH PHÂN TRANG
$so_dong_moi_trang = 15; 
$trang_hien_tai = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// 3. XÂY DỰNG ĐIỀU KIỆN WHERE
$where_sql = "WHERE DATE(ds.ngay_dat) = ?";
$param_types = "s";            
$params = [$filter_date];      

if ($pay_method != 'all') {
    $where_sql .= " AND tt.phuong_thuc = ?";
    $param_types .= "s";
    $params[] = $pay_method;
}

// 4. ĐẾM TỔNG SỐ BẢN GHI
$sql_count = "SELECT COUNT(*) as total
              FROM thanh_toan tt
              JOIN dat_san ds ON tt.dat_san_id = ds.dat_san_id
              $where_sql";

$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param($param_types, ...$params);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$tong_so_trang = ceil($total_records / $so_dong_moi_trang);
if ($trang_hien_tai > $tong_so_trang && $tong_so_trang > 0) {
    $trang_hien_tai = $tong_so_trang;
}
$offset = ($trang_hien_tai - 1) * $so_dong_moi_trang;

// 5. TRUY VẤN DỮ LIỆU HIỂN THỊ (LẤY ĐÚNG GIÁ SÂN 7)
$sql = "SELECT tt.thanh_toan_id, nd.ho_ten, sb.ten_san, ds.ten_san_con, ds.loai_san_dat, kg.gia_tien, kg.gia_tien_san_7, tt.phuong_thuc, tt.trang_thai, ds.ngay_dat, ds.dat_san_id, ds.trang_thai as ds_trang_thai
        FROM thanh_toan tt
        JOIN dat_san ds ON tt.dat_san_id = ds.dat_san_id
        JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
        JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
        JOIN san_bong sb ON kg.san_id = sb.san_id
        $where_sql
        ORDER BY tt.thanh_toan_id DESC
        LIMIT ? OFFSET ?";

$param_types .= "ii";
$params[] = $so_dong_moi_trang;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Bảng kê giao dịch</h1>
        <p class="page-subtitle">Quản lý chi tiết dòng tiền từ các kênh thanh toán.</p>
    </div>
    
    <div class="page-actions">
        <button type="button" class="btn-outline-modern" style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: #15803d; border-color: #bbf7d0;" onclick="openExportModal('xuat_excel.php?date=<?php echo $filter_date; ?>&method=<?php echo $pay_method; ?>', '<?php echo $date_display; ?>', '<?php echo $pay_method === 'all' ? 'Tất cả phương thức' : ($pay_method === 'tien_mat' ? 'Tiền mặt' : 'Chuyển khoản (VietQR)'); ?>')">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </button>
        <a href="index.php?date=<?php echo $filter_date; ?>" class="btn-primary-modern">
            Xem tổng quan
        </a>
    </div>
</div>

<!-- FILTER BAR CARD -->
<div class="admin-form-card" style="padding: 16px 24px; margin-bottom: 20px;">
    <form method="GET" action="chitiet.php" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 180px;">
            <label class="form-label" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Thời gian</label>
            <input type="date" name="date" value="<?php echo $filter_date; ?>" min="<?php echo $min_date; ?>" class="form-control" required>
        </div>
        
        <div style="flex: 1; min-width: 180px;">
            <label class="form-label" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Phương thức</label>
            <select name="method" class="form-control">
                <option value="all">Tất cả phương thức</option>
                <option value="tien_mat" <?php echo ($pay_method=='tien_mat')?'selected':''; ?>>Tiền mặt</option>
                <option value="chuyen_khoan" <?php echo ($pay_method=='chuyen_khoan')?'selected':''; ?>>Chuyển khoản (VietQR)</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-primary-modern" style="padding: 10px 18px;">
                Lọc dữ liệu
            </button>
            <a href="chitiet.php" class="btn-outline-modern" style="padding: 10px 14px;">
                Hôm nay
            </a>
        </div>
    </form>
</div>

<!-- DATA TABLE CARD (STITCH IMAGE 5) -->
<div class="table-card">
    <div class="table-card-header">
        <h3 class="table-card-title">Danh sách giao dịch ngày <?php echo $date_display; ?></h3>
        <span style="font-size: 0.85rem; color: var(--text-muted);">Tổng cộng: <strong><?php echo $total_records; ?></strong> giao dịch</span>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>MÃ GD / NGÀY GIỜ</th>
                    <th>KHÁCH HÀNG / SÂN</th>
                    <th>PHƯƠNG THỨC</th>
                    <th>SỐ TIỀN</th>
                    <th>TRẠNG THÁI</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0) : ?>
                    <?php while ($row = $result->fetch_assoc()) : 
                        $gia_hien_thi = ($row['loai_san_dat'] === 'san_7' && !empty($row['gia_tien_san_7'])) ? $row['gia_tien_san_7'] : $row['gia_tien'];
                    ?>
                    <tr>
                        <!-- Mã GD & Ngày giờ -->
                        <td>
                            <strong style="color: var(--text-heading); font-size: 0.92rem;">#TXN-<?php echo $row['thanh_toan_id']; ?></strong>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                <?php echo date("d/m/Y, H:i", strtotime($row['ngay_dat'])); ?>
                            </div>
                        </td>

                        <!-- Khách hàng / Sân -->
                        <td>
                            <strong style="color: var(--text-heading);"><?php echo htmlspecialchars($row['ho_ten']); ?></strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($row['ten_san']); ?><?php echo !empty($row['ten_san_con']) ? ' • Sân '.$row['ten_san_con'] : ''; ?>
                            </div>
                        </td>

                        <!-- Phương thức -->
                        <td>
                            <?php if ($row['phuong_thuc'] == 'chuyen_khoan'): ?>
                                <span class="pay-tag pay-tag-qr">VietQR</span>
                            <?php else: ?>
                                <span class="pay-tag pay-tag-cash">Tiền mặt</span>
                            <?php endif; ?>
                        </td>

                        <!-- Số tiền -->
                        <td>
                            <div style="font-weight: 800; font-size: 0.95rem; color: #15803d;">
                                + <?php echo number_format($gia_hien_thi); ?> đ
                            </div>
                        </td>

                        <!-- Trạng thái -->
                        <td>
                            <?php 
                            $tt_status = $row['trang_thai'];
                            $ds_status = $row['ds_trang_thai'] ?? '';

                            if ($tt_status == 'da_thanh_toan' || $ds_status == 'Đã xác nhận') {
                                echo '<span class="badge badge-xacnhan"><span class="badge-dot"></span> Hoàn tất</span>';
                            } elseif ($tt_status == 'cho_xac_nhan' || $tt_status == 'cho_xu_ly' || $ds_status == 'Chờ xác nhận') {
                                echo '<span class="badge badge-cho"><span class="badge-dot"></span> Chờ duyệt</span>';
                            } elseif ($ds_status == 'Chờ hoàn tiền') {
                                echo '<span class="badge badge-hoantien"><span class="badge-dot"></span> Chờ hoàn tiền</span>';
                            } elseif ($ds_status == 'Đã hoàn tiền') {
                                echo '<span class="badge badge-dahoan"><span class="badge-dot"></span> Đã hoàn tiền</span>';
                            } else {
                                echo '<span class="badge badge-huy"><span class="badge-dot"></span> Đã hủy</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-receipt" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                            <p style="font-size: 0.95rem; font-weight: 500;">Không có giao dịch nào trong ngày <?php echo $date_display; ?>.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PHÂN TRANG -->
    <?php if ($tong_so_trang > 1) : ?>
        <div style="padding: 16px 24px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="font-size: 0.85rem; color: var(--text-muted);">
                Hiển thị <strong><?php echo min(($offset + 1), $total_records); ?> - <?php echo min(($offset + $so_dong_moi_trang), $total_records); ?></strong> trong số <strong><?php echo $total_records; ?></strong> giao dịch
            </div>
            
            <div style="display: flex; gap: 6px;">
                <?php for ($i = 1; $i <= $tong_so_trang; $i++) : ?>
                    <a href="chitiet.php?date=<?php echo $filter_date; ?>&method=<?php echo $pay_method; ?>&page=<?php echo $i; ?>" 
                       style="padding: 6px 12px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid <?php echo ($i == $trang_hien_tai) ? 'var(--primary)' : 'var(--border)'; ?>; background: <?php echo ($i == $trang_hien_tai) ? 'var(--primary)' : '#ffffff'; ?>; color: <?php echo ($i == $trang_hien_tai) ? '#ffffff' : 'var(--text-body)'; ?>;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Xác Nhận Xuất Excel -->
<div id="exportExcelModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:99999; justify-content:center; align-items:center; backdrop-filter:blur(4px);">
    <div style="background:white; border-radius:16px; width:90%; max-width:440px; padding:24px; text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 16px;">
            <i class="fas fa-file-excel"></i>
        </div>
        
        <h3 style="margin:0 0 8px; font-size:1.2rem; font-weight:800; color:var(--text-heading);">
            Xác nhận xuất báo cáo Excel?
        </h3>
        <p style="margin:0 0 16px; font-size:0.9rem; color:var(--text-muted); line-height:1.5;">
            Bạn có chắc chắn muốn xuất dữ liệu bảng kê giao dịch ra file Excel không?
        </p>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px; margin-bottom:20px; text-align:left; font-size:0.86rem; color:#475569;">
            <div style="margin-bottom: 6px;"><strong>Thời gian:</strong> <span id="modalExportDate" style="color:var(--text-heading); font-weight:700;">Ngày <?php echo $date_display; ?></span></div>
            <div style="margin-bottom: 6px;"><strong>Phương thức:</strong> <span id="modalExportMethod" style="color:var(--text-heading); font-weight:600;"><?php echo $pay_method === 'all' ? 'Tất cả' : ($pay_method === 'tien_mat' ? 'Tiền mặt' : 'Chuyển khoản'); ?></span></div>
            <div><strong>Định dạng:</strong> <span style="color:#16a34a; font-weight:700;">Microsoft Excel (.xls)</span></div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeExportModal()" style="padding:9px 18px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:8px; font-weight:600; cursor:pointer; color:#64748b;">
                Hủy bỏ
            </button>
            <a id="btnConfirmExport" href="xuat_excel.php?date=<?php echo $filter_date; ?>&method=<?php echo $pay_method; ?>" class="btn-primary-modern" style="background:#16a34a; border-color:#16a34a; text-decoration:none; padding:9px 18px; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-download"></i> Đồng ý xuất
            </a>
        </div>
    </div>
</div>

<script>
function openExportModal(url, dateStr, methodStr) {
    const modal = document.getElementById('exportExcelModal');
    const link = document.getElementById('btnConfirmExport');
    const dateSpan = document.getElementById('modalExportDate');
    const methodSpan = document.getElementById('modalExportMethod');
    if (link && url) link.href = url;
    if (dateSpan && dateStr) dateSpan.textContent = 'Ngày ' + dateStr;
    if (methodSpan && methodStr) methodSpan.textContent = methodStr;
    if (modal) modal.style.display = 'flex';
}

function closeExportModal() {
    const modal = document.getElementById('exportExcelModal');
    if (modal) modal.style.display = 'none';
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('exportExcelModal');
    if (modal && e.target === modal) {
        closeExportModal();
    }
});

document.getElementById('btnConfirmExport')?.addEventListener('click', function() {
    setTimeout(closeExportModal, 800);
});
</script>

<?php 
$stmt->close();
include '../footer_admin.php'; 
?>