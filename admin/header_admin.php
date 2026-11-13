<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_admin();

// Đếm số đơn chờ xác nhận để hiện badge trên sidebar và chuông thông báo
$badge_count_cho = 0;
$recent_admin_notifs = [];
if (isset($conn)) {
    $res_cho = $conn->query("SELECT COUNT(*) FROM dat_san WHERE trang_thai = 'Chờ xác nhận' AND deleted_at IS NULL");
    if ($res_cho) {
        $badge_count_cho = (int)$res_cho->fetch_row()[0];
    }

    // Lấy 6 đơn đặt sân / thanh toán mới nhất để hiện trong Popup thông báo
    $res_notif = $conn->query("
        SELECT ds.dat_san_id, ds.ngay_dat, ds.trang_thai, nd.ho_ten, sb.ten_san, ds.ten_san_con, 
               kg.gio_bat_dau, kg.gio_ket_thuc, tt.phuong_thuc
        FROM dat_san ds
        JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
        JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
        JOIN san_bong sb ON kg.san_id = sb.san_id
        LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
        WHERE ds.deleted_at IS NULL AND ds.trang_thai != 'Chờ thanh toán'
        ORDER BY ds.dat_san_id DESC
        LIMIT 6
    ");
    if ($res_notif) {
        while ($rn = $res_notif->fetch_assoc()) {
            $recent_admin_notifs[] = $rn;
        }
    }
}

$current_uri = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo function_exists('csrf_token') ? csrf_token() : ''; ?>">
    <title>Quản Trị Viên</title>
    
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Admin Master CSS -->
    <link rel="stylesheet" href="/quanlysanbong/admin/style_admin.css?v=1.0.2">
</head>
<body>

<div class="admin-container">
    
    <!-- LEFT SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="/quanlysanbong/admin/" class="admin-brand">
                <img src="/quanlysanbong/images/logo.jpg" alt="Logo VH15" class="admin-brand-logo">
                <div class="admin-brand-text">
                    <span class="brand-title">Trang Quản Trị</span>
                    <span class="brand-sub">Quản Lý Sân Bóng</span>
                </div>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <?php
                    $is_home = (strpos($current_uri, '/admin/index.php') !== false || $current_uri === '/quanlysanbong/admin/' || $current_uri === '/quanlysanbong/admin');
                    $is_san = strpos($current_uri, '/admin/sanbong') !== false;
                    $is_khunggio = strpos($current_uri, '/admin/khunggio') !== false;
                    $is_datsan = strpos($current_uri, '/admin/datsan') !== false;
                    $is_user = strpos($current_uri, '/admin/user') !== false;
                    $is_doanhthu = strpos($current_uri, '/admin/doanhthu') !== false;
                    $is_danhgia = strpos($current_uri, '/admin/danhgia') !== false;
                ?>
                <li>
                    <a href="/quanlysanbong/admin/" class="<?php echo $is_home ? 'active' : ''; ?>">
                        <i class="fas fa-th-large fa-fw"></i>
                        <span class="text">Tổng quan</span>
                    </a>
                </li>
                <li>
                    <a href="/quanlysanbong/admin/sanbong/" class="<?php echo $is_san ? 'active' : ''; ?>">
                        <i class="fas fa-futbol fa-fw"></i>
                        <span class="text">Quản lý Sân</span>
                    </a>
                </li>
                <li>
                    <a href="/quanlysanbong/admin/khunggio/" class="<?php echo $is_khunggio ? 'active' : ''; ?>">
                        <i class="fas fa-clock fa-fw"></i>
                        <span class="text">Khung giờ &amp; Giá</span>
                    </a>
                </li>
                <li>
                    <a href="/quanlysanbong/admin/datsan/" class="<?php echo $is_datsan ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check fa-fw"></i>
                        <span class="text">Quản lý Đặt sân</span>
                        <?php if ($badge_count_cho > 0): ?>
                            <span class="nav-badge"><?php echo $badge_count_cho; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="/quanlysanbong/admin/user/" class="<?php echo $is_user ? 'active' : ''; ?>">
                        <i class="fas fa-users fa-fw"></i>
                        <span class="text">Khách hàng</span>
                    </a>
                </li>
                <li>
                    <a href="/quanlysanbong/admin/doanhthu/" class="<?php echo $is_doanhthu ? 'active' : ''; ?>">
                        <i class="fas fa-wallet fa-fw"></i>
                        <span class="text">Doanh thu</span>
                    </a>
                </li>
                <li>
                    <a href="/quanlysanbong/admin/danhgia/" class="<?php echo $is_danhgia ? 'active' : ''; ?>">
                        <i class="fas fa-star fa-fw"></i>
                        <span class="text">Đánh giá</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- NÚT ĐĂNG XUẤT ĐÁY SIDEBAR GÓC TRÁI -->
        <div class="sidebar-footer">
            <a href="/quanlysanbong/admin/logout.php" class="btn-logout" title="Đăng xuất khỏi hệ thống" onclick="handleAdminLogout(event, this.href);">
                <i class="fas fa-sign-out-alt fa-fw"></i>
                <span class="text">Đăng xuất</span>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="main-wrapper">
        <header class="admin-topbar">
            <div></div>
            
            <div class="topbar-actions">
                
                <!-- NOTIFICATION BELL & POPUP -->
                <div class="topbar-dropdown-wrapper" id="notifDropdownWrapper">
                    <button type="button" class="topbar-icon-btn" id="notifBellBtn" title="Thông báo đặt sân &amp; thanh toán" onclick="toggleNotifDropdown(event)">
                        <i class="far fa-bell"></i>
                        <?php if ($badge_count_cho > 0): ?>
                            <span class="dot-badge"></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="topbar-dropdown-menu notif-menu" id="notifDropdownMenu">
                        <div class="dropdown-header">
                            <div>
                                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-heading); margin: 0;">Thông báo mới</h4>
                                <span style="font-size: 0.78rem; color: var(--text-muted);">
                                    <?php echo $badge_count_cho > 0 ? "Có $badge_count_cho đơn đang chờ duyệt" : "Không có đơn chờ duyệt mới"; ?>
                                </span>
                            </div>
                            <?php if ($badge_count_cho > 0): ?>
                                <span class="badge badge-cho" style="font-size: 0.72rem; padding: 2px 7px;"><?php echo $badge_count_cho; ?> mới</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="notif-list">
                            <?php if (!empty($recent_admin_notifs)): ?>
                                <?php foreach ($recent_admin_notifs as $nf): 
                                    $is_cho = ($nf['trang_thai'] === 'Chờ xác nhận');
                                    $first_c = mb_substr(trim($nf['ho_ten']), 0, 1, 'UTF-8');
                                ?>
                                <a href="/quanlysanbong/admin/datsan/?status=<?php echo $is_cho ? 'cho-xac-nhan' : 'tat_ca'; ?>" class="notif-item <?php echo $is_cho ? 'notif-unread' : ''; ?>">
                                    <div class="avatar-letter <?php echo $is_cho ? 'avatar-amber' : 'avatar-teal'; ?>" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        <?php echo htmlspecialchars(mb_strtoupper($first_c, 'UTF-8')); ?>
                                    </div>
                                    <div class="notif-content">
                                        <p class="notif-title">
                                            <strong><?php echo htmlspecialchars($nf['ho_ten']); ?></strong> 
                                            <?php if ($nf['phuong_thuc'] == 'chuyen_khoan'): ?>
                                                đã thanh toán <span class="pay-tag pay-tag-qr" style="font-size: 10px; padding: 1px 5px;">VietQR</span>
                                            <?php else: ?>
                                                đã đặt sân <span class="pay-tag pay-tag-cash" style="font-size: 10px; padding: 1px 5px;">Tiền mặt</span>
                                            <?php endif; ?>
                                        </p>
                                        <span class="notif-sub">
                                            <?php echo htmlspecialchars($nf['ten_san']); ?><?php echo !empty($nf['ten_san_con']) ? ' • Sân '.$nf['ten_san_con'] : ''; ?> • <?php echo date("H:i", strtotime($nf['gio_bat_dau'])); ?> (<?php echo date("d/m", strtotime($nf['ngay_dat'])); ?>)
                                        </span>
                                    </div>
                                    <?php if ($is_cho): ?>
                                        <span class="notif-dot"></span>
                                    <?php endif; ?>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 30px 20px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                                    <i class="far fa-bell-slash" style="font-size: 1.8rem; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                    Chưa có thông báo nào
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="dropdown-footer">
                            <a href="/quanlysanbong/admin/datsan/" style="color: var(--primary); font-size: 0.85rem; font-weight: 700; display: block; text-align: center;">
                                Xem toàn bộ lịch đặt sân &rarr;
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- EXTERNAL LINK TO CUSTOMER SITE -->
                <a href="/quanlysanbong/index.php" target="_blank" class="topbar-icon-btn" title="Mở trang chủ người dùng">
                    <i class="fas fa-external-link-alt"></i>
                </a>

            </div>
        </header>

        <main class="admin-content">
