<?php
session_start();
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/header_admin.php'; 

require_admin();

use Models\DatSan;
use Models\DoanhThu;
use Models\User;
use Models\SanBong;

// --- 1. THỐNG KÊ SỐ LIỆU QUA MODELS (CHUẨN MVC) ---
$cho_xac_nhan = DatSan::countPending();
$doanh_thu = DoanhThu::getTotalRevenue();
$tong_don = DatSan::countTotal();
$tong_khach = User::countCustomers();
$tong_san = SanBong::countAll();

// Dữ liệu biểu đồ 7 ngày
$chartInfo = DoanhThu::getRevenueLast7Days();
$labels = $chartInfo['labels'];
$data_chart = $chartInfo['data_chart'];

// Danh sách 4 đơn chờ duyệt gần nhất
$pending_bookings_query = $conn->query("
    SELECT ds.dat_san_id, ds.ngay_dat, ds.ten_san_con, nd.ho_ten, sb.ten_san, kg.gio_bat_dau, kg.gio_ket_thuc
    FROM dat_san ds
    JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
    JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
    JOIN san_bong sb ON kg.san_id = sb.san_id
    WHERE ds.trang_thai = 'Chờ xác nhận' AND ds.deleted_at IS NULL
    ORDER BY ds.dat_san_id DESC
    LIMIT 4
");
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tổng quan hệ thống</h1>
        <p class="page-subtitle">Hoạt động trong ngày hôm nay, <?php echo date('d/m/Y'); ?></p>
    </div>
</div>

<!-- 5 STAT METRICS CARDS -->
<div class="stats-grid-5">
    <a href="datsan/index.php?status=cho-xac-nhan" class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-orange">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <?php if ($cho_xac_nhan > 0): ?>
                <span class="stat-tag tag-red">Cần xử lý</span>
            <?php else: ?>
                <span class="stat-tag tag-green">Ổn định</span>
            <?php endif; ?>
        </div>
        <div class="stat-card-bottom">
            <h4>Đơn chờ duyệt</h4>
            <div class="stat-number"><?php echo number_format($cho_xac_nhan); ?></div>
        </div>
    </a>

    <a href="doanhthu/index.php" class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-green">
                <i class="fas fa-wallet"></i>
            </div>
            <span class="stat-tag tag-green">Tăng trưởng</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Doanh thu (VNĐ)</h4>
            <div class="stat-number"><?php echo number_format($doanh_thu); ?> đ</div>
        </div>
    </a>

    <a href="datsan/index.php" class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-purple">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <span class="stat-tag tag-blue">Đã ghi nhận</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Tổng đơn đặt</h4>
            <div class="stat-number"><?php echo number_format($tong_don); ?></div>
        </div>
    </a>

    <a href="user/index.php" class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-blue">
                <i class="fas fa-users"></i>
            </div>
            <span class="stat-tag tag-blue">Thành viên</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Khách hàng</h4>
            <div class="stat-number"><?php echo number_format($tong_khach); ?></div>
        </div>
    </a>

    <a href="sanbong/index.php" class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-teal">
                <i class="fas fa-futbol"></i>
            </div>
            <span class="stat-tag tag-green">Hoạt động</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Sân bóng</h4>
            <div class="stat-number"><?php echo number_format($tong_san); ?></div>
        </div>
    </a>
</div>

<!-- 2-COLUMN MAIN GRID (CHART & QUICK PENDING LIST) -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    
    <!-- LEFT: REVENUE CHART -->
    <div class="table-card">
        <div class="table-card-header">
            <div>
                <h3 class="table-card-title">Doanh thu 7 ngày qua</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">Biến động doanh thu theo ngày (VNĐ)</p>
            </div>
            <span style="font-size: 0.85rem; background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-weight: 600; color: #475569;">
                7 ngày gần nhất
            </span>
        </div>
        <div style="padding: 24px; min-height: 320px;">
            <canvas id="revenueChart" style="max-height: 300px; width: 100%;"
                    data-labels='<?php echo json_encode($labels); ?>' 
                    data-revenue='<?php echo json_encode($data_chart); ?>'>
            </canvas>
        </div>
    </div>

    <!-- RIGHT: QUICK PENDING BOOKINGS WIDGET -->
    <div class="table-card">
        <div class="table-card-header">
            <h3 class="table-card-title">Đơn chờ duyệt</h3>
            <a href="datsan/index.php?status=cho-xac-nhan" style="color: var(--primary); font-size: 0.85rem; font-weight: 700;">Xem tất cả</a>
        </div>
        
        <div style="padding: 16px 20px;">
            <?php if ($pending_bookings_query && $pending_bookings_query->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php while($p = $pending_bookings_query->fetch_assoc()): 
                        $first_letter = mb_substr(trim($p['ho_ten']), 0, 1, 'UTF-8');
                    ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-light);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="avatar-letter avatar-blue">
                                <?php echo htmlspecialchars(mb_strtoupper($first_letter, 'UTF-8')); ?>
                            </div>
                            <div>
                                <strong style="color: var(--text-heading); font-size: 0.9rem; display: block;"><?php echo htmlspecialchars($p['ho_ten']); ?></strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($p['ten_san']); ?><?php echo !empty($p['ten_san_con']) ? ' • '.$p['ten_san_con'] : ''; ?>
                                </span>
                                <div style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">
                                    <?php echo date("H:i", strtotime($p['gio_bat_dau'])) . ' - ' . date("H:i", strtotime($p['gio_ket_thuc'])); ?> • <?php echo date("d/m", strtotime($p['ngay_dat'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-buttons-group">
                            <a href="datsan/capnhat.php?id=<?php echo $p['dat_san_id']; ?>&action=xacnhan&status_return=tat_ca" class="btn-action-icon btn-accept" title="Duyệt đơn này">
                                <i class="fas fa-check"></i>
                            </a>
                            <a href="datsan/capnhat.php?id=<?php echo $p['dat_san_id']; ?>&action=huy&status_return=tat_ca" class="btn-action-icon btn-cancel" title="Hủy đơn này" onclick="return confirm('Hủy đơn đặt sân này?');">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 10px; color: var(--text-muted);">
                    <i class="fas fa-check-circle" style="font-size: 2rem; color: #10b981; margin-bottom: 8px;"></i>
                    <p style="font-size: 0.9rem; font-weight: 500;">Hiện không có đơn nào chờ duyệt</p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 18px;">
                <a href="datsan/index.php" class="btn-outline-modern" style="width: 100%; justify-content: center; font-size: 0.85rem;">
                    Xem toàn bộ lịch đặt
                </a>
            </div>
        </div>
    </div>

</div>

<?php include 'footer_admin.php'; ?>