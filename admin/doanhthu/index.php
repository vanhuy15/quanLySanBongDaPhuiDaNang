<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php'; 

require_admin();

$min_date = '2025-01-01';
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if ($filter_date < $min_date) {
    $filter_date = $min_date;
}
$max_date = date('Y-m-d'); 
$date_display = date("d/m/Y", strtotime($filter_date));

// Tính toán số liệu
$sql_total = "SELECT SUM(
                CASE WHEN ds.loai_san_dat = 'san_7' AND kg.gia_tien_san_7 IS NOT NULL AND kg.gia_tien_san_7 > 0
                     THEN kg.gia_tien_san_7
                     ELSE kg.gia_tien
                END
              ) as total, COUNT(*) as orders 
              FROM dat_san ds 
              JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
              WHERE ds.trang_thai = 'Đã xác nhận' 
              AND DATE(ds.ngay_dat) = ?";
              
$stmt = $conn->prepare($sql_total);
$stmt->bind_param("s", $filter_date);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$doanh_thu_ngay = $stats['total'] ?? 0;
$so_don_ngay = $stats['orders'] ?? 0;
$stmt->close();

$sql_san = "SELECT sb.ten_san, SUM(
                CASE WHEN ds.loai_san_dat = 'san_7' AND kg.gia_tien_san_7 IS NOT NULL AND kg.gia_tien_san_7 > 0
                     THEN kg.gia_tien_san_7
                     ELSE kg.gia_tien
                END
            ) as revenue 
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            JOIN san_bong sb ON kg.san_id = sb.san_id
            WHERE ds.trang_thai = 'Đã xác nhận' 
            AND DATE(ds.ngay_dat) = ?
            GROUP BY sb.san_id";
            
$stmt_san = $conn->prepare($sql_san);
$stmt_san->bind_param("s", $filter_date);
$stmt_san->execute();
$res_san = $stmt_san->get_result();

$labels = [];
$data = [];
$top_san_name = "Chưa có";
$max_revenue = 0;

while($row = $res_san->fetch_assoc()){
    $labels[] = $row['ten_san'];
    $data[] = (int)$row['revenue'];
    
    if($row['revenue'] > $max_revenue){
        $max_revenue = $row['revenue'];
        $top_san_name = $row['ten_san'];
    }
}
$stmt_san->close();
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Báo Cáo Doanh Thu</h1>
        <p class="page-subtitle">Thống kê chi tiết doanh thu theo từng ngày và cơ cấu từng sân bóng.</p>
    </div>
    
    <div class="page-actions">
        <form method="GET" action="index.php" style="display: flex; gap: 8px; align-items: center;">
            <input type="date" name="date" value="<?php echo $filter_date; ?>" min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>" 
                   style="padding: 8px 14px; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.88rem; outline: none; font-weight: 500; color: var(--text-heading);" required>
            <button type="submit" class="btn-primary-modern" style="padding: 8px 14px; font-size: 0.85rem;">
                Xem
            </button>
        </form>
        <button type="button" onclick="openExportModal('xuat_excel.php?date=<?php echo $filter_date; ?>', '<?php echo $date_display; ?>')" class="btn-outline-modern" style="color: #15803d; border-color: #bbf7d0; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </button>
    </div>
</div>

<!-- 3 SUMMARY STAT CARDS -->
<div class="stats-grid-3">
    <div class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-green"><i class="fas fa-money-bill-wave"></i></div>
            <span class="stat-tag tag-green">Ngày <?php echo $date_display; ?></span>
        </div>
        <div class="stat-card-bottom">
            <h4>Doanh thu trong ngày</h4>
            <div class="stat-number"><?php echo number_format($doanh_thu_ngay); ?> đ</div>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-purple"><i class="fas fa-check-circle"></i></div>
            <span class="stat-tag tag-blue">Đã hoàn thành</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Số đơn thành công</h4>
            <div class="stat-number"><?php echo $so_don_ngay; ?></div>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-teal"><i class="fas fa-trophy"></i></div>
            <span class="stat-tag tag-orange">Top 1</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Sân doanh thu cao nhất</h4>
            <div class="stat-number" style="font-size: 1.15rem;"><?php echo htmlspecialchars($top_san_name); ?></div>
        </div>
    </div>
</div>

<!-- CHART CARD -->
<div class="table-card" style="margin-bottom: 24px;">
    <div class="table-card-header">
        <h3 class="table-card-title">Cơ cấu doanh thu theo từng sân (Ngày <?php echo $date_display; ?>)</h3>
        <a href="chitiet.php?date=<?php echo $filter_date; ?>" class="btn-primary-modern" style="padding: 6px 14px; font-size: 0.85rem;">
            <i class="fas fa-list"></i> Xem chi tiết các giao dịch
        </a>
    </div>
    <div style="padding: 24px; min-height: 320px;">
        <canvas id="revenueByFieldChart" style="max-height: 300px; width: 100%;"
                data-labels='<?php echo json_encode($labels); ?>' 
                data-revenue='<?php echo json_encode($data); ?>'>
        </canvas>
    </div>
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
            Bạn có chắc chắn muốn xuất dữ liệu báo cáo doanh thu ra file Excel không?
        </p>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px; margin-bottom:20px; text-align:left; font-size:0.86rem; color:#475569;">
            <div style="margin-bottom: 6px;"><strong>Thời gian:</strong> <span id="modalExportDate" style="color:var(--text-heading); font-weight:700;">Ngày <?php echo $date_display; ?></span></div>
            <div><strong>Định dạng file:</strong> <span style="color:#16a34a; font-weight:700;">Microsoft Excel (.xls)</span></div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeExportModal()" style="padding:9px 18px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:8px; font-weight:600; cursor:pointer; color:#64748b;">
                Hủy bỏ
            </button>
            <a id="btnConfirmExport" href="xuat_excel.php?date=<?php echo $filter_date; ?>" class="btn-primary-modern" style="background:#16a34a; border-color:#16a34a; text-decoration:none; padding:9px 18px; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-download"></i> Đồng ý xuất
            </a>
        </div>
    </div>
</div>

<script>
function openExportModal(url, dateStr) {
    const modal = document.getElementById('exportExcelModal');
    const link = document.getElementById('btnConfirmExport');
    const dateSpan = document.getElementById('modalExportDate');
    if (link && url) link.href = url;
    if (dateSpan && dateStr) dateSpan.textContent = 'Ngày ' + dateStr;
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

<?php include '../footer_admin.php'; ?>