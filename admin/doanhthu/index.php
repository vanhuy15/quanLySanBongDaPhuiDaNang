<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php'; 

require_admin();

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
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
            <input type="date" name="date" value="<?php echo $filter_date; ?>" max="<?php echo $max_date; ?>" 
                   style="padding: 8px 14px; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.88rem; outline: none; font-weight: 500; color: var(--text-heading);" required>
            <button type="submit" class="btn-primary-modern" style="padding: 8px 14px; font-size: 0.85rem;">
                Xem
            </button>
        </form>
        <a href="xuat_excel.php?date=<?php echo $filter_date; ?>" class="btn-outline-modern" style="color: #15803d; border-color: #bbf7d0;">
            Xuất Excel
        </a>
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

<?php include '../footer_admin.php'; ?>