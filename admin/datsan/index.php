<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php'; 

require_admin();

// --- 1. BỘ LỌC NGÀY VÀ TRẠNG THÁI ---
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'tat_ca';
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';

// --- 1. BỘ LỌC NGÀY VÀ TRẠNG THÁI ---
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'tat_ca';
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';

$where_clauses = ["ds.deleted_at IS NULL", "ds.trang_thai != 'Chờ thanh toán'"];
$count_clauses = ["deleted_at IS NULL", "trang_thai != 'Chờ thanh toán'"];
$params_date = [];
$types_date = "";

if (!empty($date_filter) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $where_clauses[] = "ds.ngay_dat = ?";
    $count_clauses[] = "ngay_dat = ?";
    $params_date[] = $date_filter;
    $types_date .= "s";
}

// Đếm số lượng tập trung (1 truy vấn tối ưu thay cho 6 truy vấn rời rạc)
$count_where_sql = "WHERE " . implode(" AND ", $count_clauses);
$sql_counts = "
    SELECT 
        COUNT(*) AS total_all,
        COALESCE(SUM(trang_thai = 'Chờ xác nhận'), 0) AS total_cho,
        COALESCE(SUM(trang_thai = 'Đã xác nhận'), 0) AS total_xacnhan,
        COALESCE(SUM(trang_thai = 'Chờ hoàn tiền'), 0) AS total_cho_hoan,
        COALESCE(SUM(trang_thai = 'Đã hoàn tiền'), 0) AS total_da_hoan,
        COALESCE(SUM(trang_thai IN ('Hủy', 'Đã hủy')), 0) AS total_huy
    FROM dat_san $count_where_sql
";
$stmt_c = $conn->prepare($sql_counts);
if (!empty($params_date)) {
    $stmt_c->bind_param($types_date, ...$params_date);
}
$stmt_c->execute();
$counts_row = $stmt_c->get_result()->fetch_assoc();
$stmt_c->close();

$count_all = (int)($counts_row['total_all'] ?? 0);
$count_cho = (int)($counts_row['total_cho'] ?? 0);
$count_xacnhan = (int)($counts_row['total_xacnhan'] ?? 0);
$count_cho_hoan = (int)($counts_row['total_cho_hoan'] ?? 0);
$count_da_hoan = (int)($counts_row['total_da_hoan'] ?? 0);
$count_huy = (int)($counts_row['total_huy'] ?? 0);

switch ($status_filter) {
    case 'cho-xac-nhan': $where_clauses[] = "ds.trang_thai = 'Chờ xác nhận'"; break;
    case 'da-xac-nhan': $where_clauses[] = "ds.trang_thai = 'Đã xác nhận'"; break;
    case 'cho-hoan-tien': $where_clauses[] = "ds.trang_thai = 'Chờ hoàn tiền'"; break;
    case 'da-hoan-tien': $where_clauses[] = "ds.trang_thai = 'Đã hoàn tiền'"; break;
    case 'huy': $where_clauses[] = "ds.trang_thai IN ('Hủy', 'Đã hủy')"; break;
    default: break;
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// --- 2. LOGIC PHÂN TRANG ---
$so_luong_moi_trang = 10;
$trang_hien_tai = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($trang_hien_tai < 1) $trang_hien_tai = 1;

$sql_count_filter = "SELECT COUNT(*) FROM dat_san ds $where_sql";
$stmt_cf = $conn->prepare($sql_count_filter);
if (!empty($params_date)) {
    $stmt_cf->bind_param($types_date, ...$params_date);
}
$stmt_cf->execute();
$total_records = (int)($stmt_cf->get_result()->fetch_row()[0] ?? 0);
$stmt_cf->close();

$tong_so_trang = ceil($total_records / $so_luong_moi_trang);
if ($trang_hien_tai > $tong_so_trang && $tong_so_trang > 0) $trang_hien_tai = $tong_so_trang;
$offset = ($trang_hien_tai - 1) * $so_luong_moi_trang;

// --- 3. TRUY VẤN VỚI PREPARED STATEMENT ---
$sql = "SELECT ds.*, nd.ho_ten, nd.sdt, nd.so_lan_bung_san, nd.diem_uy_tin, kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien, kg.gia_tien_san_7, sb.ten_san, tt.trang_thai as tt_trang_thai, tt.phuong_thuc
        FROM dat_san ds
        JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
        JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
        JOIN san_bong sb ON kg.san_id = sb.san_id
        LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
        $where_sql
        ORDER BY ds.ngay_dat DESC, ds.dat_san_id DESC
        LIMIT ? OFFSET ?";

$params_main = $params_date;
$types_main = $types_date . "ii";
$params_main[] = $so_luong_moi_trang;
$params_main[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types_main, ...$params_main);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý Đặt sân</h1>
        <p class="page-subtitle">Kiểm soát và xử lý các yêu cầu đặt sân, trạng thái thanh toán.</p>
    </div>
    
    <div class="page-actions">
        <form method="GET" action="index.php" style="display: flex; gap: 8px; align-items: center;">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <div style="position: relative;">
                <input type="date" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>" 
                       style="padding: 8px 14px; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.88rem; outline: none; font-weight: 500; color: var(--text-heading);">
            </div>
            <button type="submit" class="btn-primary-modern" style="padding: 8px 14px; font-size: 0.85rem;">
                Lọc
            </button>
            <?php if (!empty($date_filter)): ?>
                <a href="index.php?status=<?php echo htmlspecialchars($status_filter); ?>" class="btn-outline-modern" style="padding: 8px 12px; font-size: 0.85rem; color: #ef4444;" title="Xóa lọc ngày">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- MAIN TABLE CARD -->
<div class="table-card">
    
    <!-- TABS FILTER BAR -->
    <div class="filter-tabs-bar">
        <?php 
            $base_q = "date_filter=" . urlencode($date_filter);
        ?>
        <a href="index.php?status=tat_ca<?php echo !empty($date_filter) ? '&'.$base_q : ''; ?>" class="tab-link <?php echo $status_filter=='tat_ca'?'active':''; ?>">
            Tất cả <span class="tab-counter"><?php echo $count_all; ?></span>
        </a>
        <a href="index.php?status=cho-xac-nhan<?php echo !empty($date_filter) ? '&'.$base_q : ''; ?>" class="tab-link <?php echo $status_filter=='cho-xac-nhan'?'active':''; ?>">
            Chờ duyệt <span class="tab-counter"><?php echo $count_cho; ?></span>
        </a>
        <a href="index.php?status=da-xac-nhan<?php echo !empty($date_filter) ? '&'.$base_q : ''; ?>" class="tab-link <?php echo $status_filter=='da-xac-nhan'?'active':''; ?>">
            Đã xác nhận <span class="tab-counter"><?php echo $count_xacnhan; ?></span>
        </a>
        <a href="index.php?status=cho-hoan-tien<?php echo !empty($date_filter) ? '&'.$base_q : ''; ?>" class="tab-link <?php echo $status_filter=='cho-hoan-tien'?'active':''; ?>">
            Chờ hoàn tiền <span class="tab-counter"><?php echo $count_cho_hoan; ?></span>
        </a>
        <a href="index.php?status=da-hoan-tien<?php echo !empty($date_filter) ? '&'.$base_q : ''; ?>" class="tab-link <?php echo $status_filter=='da-hoan-tien'?'active':''; ?>">
            Đã hoàn tiền <span class="tab-counter"><?php echo $count_da_hoan; ?></span>
        </a>
        <a href="index.php?status=huy<?php echo !empty($date_filter) ? '&'.$base_q : ''; ?>" class="tab-link <?php echo $status_filter=='huy'?'active':''; ?>">
            Đã hủy <span class="tab-counter"><?php echo $count_huy; ?></span>
        </a>
    </div>

    <!-- TABLE DATA -->
    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>MÃ ĐƠN</th>
                    <th>KHÁCH HÀNG</th>
                    <th>SÂN CON</th>
                    <th>GIỜ ĐÁ</th>
                    <th>SỐ TIỀN</th>
                    <th>TRẠNG THÁI</th>
                    <th style="text-align: center;">THAO TÁC</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $first_char = mb_substr(trim($row['ho_ten']), 0, 1, 'UTF-8');
                        $uy_tin = (int)($row['diem_uy_tin'] ?? 100);
                        $gia_hien_thi = ($row['loai_san_dat'] === 'san_7' && !empty($row['gia_tien_san_7'])) ? $row['gia_tien_san_7'] : $row['gia_tien'];
                        
                        $end_match_time = strtotime($row['ngay_dat'] . ' ' . $row['gio_ket_thuc']);
                        $is_match_past = ($end_match_time < time());
                    ?>
                    <tr>
                        <!-- Mã Đơn -->
                        <td>
                            <strong style="color: var(--text-heading); font-size: 0.92rem;">#PM-<?php echo $row['dat_san_id']; ?></strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?php echo date("d/m/Y", strtotime($row['ngay_dat'])); ?>
                            </div>
                        </td>
                        
                        <!-- Khách hàng -->
                        <td>
                            <div class="user-cell">
                                <div class="avatar-letter avatar-blue">
                                    <?php echo htmlspecialchars(mb_strtoupper($first_char, 'UTF-8')); ?>
                                </div>
                                <div class="user-cell-info">
                                    <span class="user-cell-name"><?php echo htmlspecialchars($row['ho_ten']); ?></span>
                                    <span class="user-cell-sub"><?php echo htmlspecialchars($row['sdt']); ?></span>
                                    <div>
                                        <?php if(!empty($row['so_lan_bung_san']) && (int)$row['so_lan_bung_san'] > 0): ?>
                                            <span class="reputation-badge rep-danger" title="CẢNH BÁO: Khách từng bùng sân <?php echo $row['so_lan_bung_san']; ?> lần!">
                                                Bùng <?php echo $row['so_lan_bung_san']; ?> lần
                                            </span>
                                        <?php else: ?>
                                            <span class="reputation-badge <?php echo $uy_tin >= 80 ? 'rep-high' : ($uy_tin >= 60 ? 'rep-warn' : 'rep-danger'); ?>">
                                                Uy tín: <?php echo $uy_tin; ?>/100
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Thông tin sân con -->
                        <td>
                            <strong style="color: var(--text-heading);"><?php echo htmlspecialchars($row['ten_san']); ?></strong>
                            <?php if(!empty($row['ten_san_con'])): ?>
                                <br><span class="pitch-sub-badge">Sân: <?php echo htmlspecialchars($row['ten_san_con']); ?></span>
                            <?php endif; ?>
                            <?php if ($row['loai_san_dat'] === 'san_7'): ?>
                                <span style="background: #e6f4f2; color: #007a64; font-size: 0.72rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">Sân 7</span>
                            <?php endif; ?>
                        </td>

                        <!-- Giờ đá -->
                        <td>
                            <div style="font-weight: 700; color: var(--text-heading); font-size: 0.92rem;">
                                <?php echo date("H:i", strtotime($row['gio_bat_dau'])) . ' - ' . date("H:i", strtotime($row['gio_ket_thuc'])); ?>
                            </div>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                <?php echo date("d/m/Y", strtotime($row['ngay_dat'])); ?>
                            </span>
                        </td>

                        <!-- Số tiền & Phương thức -->
                        <td>
                            <div class="text-amount-bold"><?php echo number_format($gia_hien_thi); ?> đ</div>
                            <?php if($row['phuong_thuc'] == 'chuyen_khoan'): ?>
                                <span class="pay-tag pay-tag-qr">VietQR</span>
                            <?php else: ?>
                                <span class="pay-tag pay-tag-cash">Tiền mặt</span>
                            <?php endif; ?>
                        </td>

                        <!-- Trạng thái -->
                        <td>
                            <?php 
                            if ($row['trang_thai'] == 'Chờ xác nhận') {
                                echo '<span class="badge badge-cho"><span class="badge-dot"></span> Chờ duyệt</span>';
                            } elseif ($row['trang_thai'] == 'Đã xác nhận') {
                                if ($is_match_past) {
                                    if ($row['phuong_thuc'] == 'tien_mat' && $row['tt_trang_thai'] != 'da_thanh_toan') {
                                        echo '<span class="badge badge-cho"><span class="badge-dot"></span> Chờ thu tiền</span>';
                                    } else {
                                        echo '<span class="badge badge-xacnhan"><span class="badge-dot"></span> Hoàn thành</span>';
                                    }
                                } else {
                                    echo '<span class="badge badge-xacnhan"><span class="badge-dot"></span> Đã xác nhận</span>';
                                }
                            } elseif ($row['trang_thai'] == 'Chờ hoàn tiền') {
                                echo '<span class="badge badge-hoantien"><span class="badge-dot"></span> Chờ hoàn tiền</span>';
                            } elseif ($row['trang_thai'] == 'Đã hoàn tiền') {
                                echo '<span class="badge badge-dahoan"><span class="badge-dot"></span> Đã hoàn tiền</span>';
                            } else {
                                echo '<span class="badge badge-huy"><span class="badge-dot"></span> ' . htmlspecialchars($row['trang_thai']) . '</span>';
                            }
                            ?>
                        </td>
                        
                        <!-- Thao tác -->
                        <td style="text-align: center;">
                            <div class="action-buttons-group" style="justify-content: center;">
                                <?php if($row['trang_thai'] == 'Chờ xác nhận'): ?>
                                    <a href="capnhat.php?id=<?php echo $row['dat_san_id']; ?>&action=xacnhan&status_return=<?php echo $status_filter; ?>" class="btn-action-icon btn-accept" title="Duyệt đơn này">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="capnhat.php?id=<?php echo $row['dat_san_id']; ?>&action=huy&status_return=<?php echo $status_filter; ?>" class="btn-action-icon btn-cancel" title="Hủy đơn" onclick="return confirm('Hủy đơn đặt sân này?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                
                                <?php elseif($row['trang_thai'] == 'Đã xác nhận'): ?>
                                    <?php if ($row['phuong_thuc'] == 'tien_mat'): ?>
                                        <?php if (!$is_match_past): ?>
                                            <a href="capnhat.php?id=<?php echo $row['dat_san_id']; ?>&action=huy&status_return=<?php echo $status_filter; ?>" class="btn-action-icon btn-cancel" title="Hủy sân trước giờ đá" onclick="return confirm('Hủy lịch đặt sân này?');">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <?php if ($row['tt_trang_thai'] == 'da_thanh_toan'): ?>
                                                <span style="color:#10b981; font-size:11px; font-weight:700; background:#ecfdf5; padding:4px 8px; border-radius:6px;">
                                                    Đã thu
                                                </span>
                                            <?php else: ?>
                                                <a href="capnhat.php?id=<?php echo $row['dat_san_id']; ?>&action=thutien_mat&status_return=<?php echo $status_filter; ?>" class="btn-action-icon btn-accept" title="Thu tiền mặt">
                                                    <i class="fas fa-hand-holding-usd"></i>
                                                </a>
                                                <a href="capnhat.php?id=<?php echo $row['dat_san_id']; ?>&action=khach_bung_san&status_return=<?php echo $status_filter; ?>" class="btn-action-icon btn-cancel" title="Báo bùng sân" onclick="return confirm('Báo cáo khách hàng bùng sân? Sẽ trừ điểm uy tín.');">
                                                    <i class="fas fa-user-slash"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (!$is_match_past): ?>
                                            <a href="capnhat.php?id=<?php echo $row['dat_san_id']; ?>&action=huy&status_return=<?php echo $status_filter; ?>" class="btn-action-icon btn-cancel" title="Hủy đơn đã chốt" onclick="return confirm('Hủy đơn đã chốt? (Nếu khách đã CK cần hoàn tiền qua mục Hoàn Tiền)');">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#64748b; font-size:11px; font-weight:600;">Đã đá xong</span>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                <?php elseif($row['trang_thai'] == 'Chờ hoàn tiền'): ?>
                                    <?php 
                                        $rf_info = json_decode($row['thong_tin_hoan_tien'] ?? '{}', true);
                                        $rf_bank = $rf_info['ngan_hang'] ?? 'MB';
                                        $rf_stk = $rf_info['stk'] ?? '';
                                        $rf_name = $rf_info['ten_chu_tk'] ?? $row['ho_ten'];
                                        $rf_amt = (int)$gia_hien_thi;
                                    ?>
                                    <button type="button" class="btn-action-icon btn-refund" onclick="openAdminRefundQR(<?php echo $row['dat_san_id']; ?>, '<?php echo addslashes($rf_bank); ?>', '<?php echo addslashes($rf_stk); ?>', '<?php echo addslashes($rf_name); ?>', <?php echo $rf_amt; ?>, '<?php echo $status_filter; ?>')" title="Quét mã VietQR hoàn tiền cho khách">
                                        <i class="fas fa-qrcode"></i>
                                    </button>

                                <?php elseif($row['trang_thai'] == 'Đã hoàn tiền'): ?>
                                    <span style="color:#0284c7; font-size:12px; font-weight:700;">Đã hoàn</span>
                                <?php else: ?>
                                    <form method="POST" action="xoa.php" style="display:inline;" onsubmit="return confirm('Xóa vĩnh viễn đơn này?');">
                                        <?php echo function_exists('csrf_field') ? csrf_field() : ''; ?>
                                        <input type="hidden" name="id" value="<?php echo $row['dat_san_id']; ?>">
                                        <input type="hidden" name="status_return" value="<?php echo htmlspecialchars($status_filter); ?>">
                                        <button type="submit" class="btn-action-icon btn-cancel" title="Xóa lịch sử" style="background:none; border:none; cursor:pointer; padding:0;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                            <i class="far fa-folder-open" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                            <p style="font-weight: 500; font-size: 0.95rem;">Không có đơn đặt sân nào phù hợp với bộ lọc</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($tong_so_trang > 1) : ?>
        <div style="padding: 16px 24px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="font-size: 0.85rem; color: var(--text-muted);">
                Hiển thị <strong><?php echo min(($offset + 1), $total_records); ?> - <?php echo min(($offset + $so_luong_moi_trang), $total_records); ?></strong> trong số <strong><?php echo $total_records; ?></strong> đơn
            </div>
            
            <div style="display: flex; gap: 6px;">
                <?php for ($i = 1; $i <= $tong_so_trang; $i++) : ?>
                    <a href="index.php?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?><?php echo !empty($date_filter) ? '&date_filter='.urlencode($date_filter) : ''; ?>" 
                       style="padding: 6px 12px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid <?php echo ($i == $trang_hien_tai) ? 'var(--primary)' : 'var(--border)'; ?>; background: <?php echo ($i == $trang_hien_tai) ? 'var(--primary)' : '#ffffff'; ?>; color: <?php echo ($i == $trang_hien_tai) ? '#ffffff' : 'var(--text-body)'; ?>;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Admin Quét QR Hoàn tiền -->
<div id="adminRefundModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:99999; justify-content:center; align-items:center; backdrop-filter:blur(4px);">
    <div style="background:white; border-radius:16px; width:90%; max-width:440px; padding:25px; text-align:center; box-shadow:0 25px 50px rgba(0,0,0,0.3); animation:fadeIn 0.25s ease;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
            <h3 style="margin:0; font-size:1.15rem; color:#1e293b;">
                Quét mã VietQR hoàn tiền cho khách
            </h3>
            <button type="button" onclick="closeAdminRefundQR()" style="background:none; border:none; font-size:1.3rem; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>

        <div style="background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:12px; padding:15px; margin-bottom:15px;">
            <img id="admin_qr_img" src="" alt="Mã VietQR hoàn tiền" style="width:100%; max-width:280px; height:auto; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
            <p style="margin:8px 0 0 0; font-size:0.85rem; color:#64748b;">Mở ứng dụng Ngân hàng để quét mã chuyển tiền hoàn</p>
        </div>

        <div style="text-align:left; background:#f1f5f9; border-radius:8px; padding:12px; margin-bottom:20px; font-size:0.9rem; line-height:1.6;">
            <div><strong>Ngân hàng:</strong> <span id="admin_qr_bank" style="color:#6d28d9; font-weight:700;"></span></div>
            <div><strong>Số tài khoản:</strong> <span id="admin_qr_stk" style="font-family:inherit; font-weight:700; color:#0284c7;"></span></div>
            <div><strong>Chủ tài khoản:</strong> <span id="admin_qr_name" style="font-weight:700;"></span></div>
            <div><strong>Số tiền hoàn:</strong> <span id="admin_qr_amt" style="font-weight:800; color:#047857;"></span></div>
        </div>

        <div style="display:flex; justify-content:space-between; gap:10px;">
            <button type="button" onclick="closeAdminRefundQR()" style="padding:9px 18px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:8px; font-weight:600; cursor:pointer; color:#64748b;">Đóng</button>
            <a id="admin_confirm_refund_link" href="#" class="btn-primary-modern" style="background:#10b981; text-decoration:none;" onclick="return confirm('Xác nhận bạn ĐÃ chuyển tiền hoàn thành công cho khách? Đơn sẽ chuyển sang Đã Hủy.');">
                Đã chuyển tiền xong
            </a>
        </div>
    </div>
</div>

<?php 
$stmt->close();
include '../footer_admin.php'; 
?>