<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/db.php';
require_admin();

$san_id = intval($_GET['id'] ?? $_GET['san_id'] ?? 0);
if ($san_id <= 0) {
    header('Location: index.php');
    exit();
}

// Xử lý xóa đánh giá (nếu có yêu cầu từ admin)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM danh_gia WHERE danh_gia_id = ? AND san_id = ?");
    $stmt->bind_param("ii", $del_id, $san_id);
    $stmt->execute();
    $stmt->close();
    header("Location: detail.php?id=$san_id&deleted=1");
    exit();
}

// Thông tin sân + thống kê đánh giá
$stmt = $conn->prepare("
    SELECT sb.*,
           COALESCE(ROUND(AVG(dg.so_sao), 1), 0) as avg_rating,
           COUNT(dg.danh_gia_id) as total_reviews
    FROM san_bong sb
    LEFT JOIN danh_gia dg ON dg.san_id = sb.san_id
    WHERE sb.san_id = ?
    GROUP BY sb.san_id
");
$stmt->bind_param("i", $san_id);
$stmt->execute();
$san = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$san) {
    header('Location: index.php');
    exit();
}

// Chỉ cho phép xem khi sân bóng đã có ít nhất 1 nhận xét
if ((int)($san['total_reviews'] ?? 0) <= 0) {
    header('Location: index.php');
    exit();
}

// Phân phối số sao (1 sao -> 5 sao)
$dist_stmt = $conn->prepare("SELECT so_sao, COUNT(*) as cnt FROM danh_gia WHERE san_id = ? GROUP BY so_sao");
$dist_stmt->bind_param("i", $san_id);
$dist_stmt->execute();
$star_dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$dr = $dist_stmt->get_result();
while ($row = $dr->fetch_assoc()) {
    $star_dist[$row['so_sao']] = (int)$row['cnt'];
}
$dist_stmt->close();

// Danh sách đánh giá có phân trang
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$total = (int)$san['total_reviews'];
$total_pages = ceil($total / $limit);

$rv_stmt = $conn->prepare("
    SELECT dg.*, nd.ho_ten, nd.sdt, nd.avatar
    FROM danh_gia dg
    JOIN nguoi_dung nd ON dg.nguoi_dung_id = nd.nguoi_dung_id
    WHERE dg.san_id = ?
    ORDER BY dg.ngay_tao DESC
    LIMIT ? OFFSET ?
");
$rv_stmt->bind_param("iii", $san_id, $limit, $offset);
$rv_stmt->execute();
$reviews = $rv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rv_stmt->close();

// Header admin được include SAU KHI xử lý xong toàn bộ logic chuyển hướng (tránh lỗi Cannot modify header information)
include '../header_admin.php';
?>

<!-- PAGE HEADER -->
<div class="page-header" style="margin-bottom: 1.5rem;">
    <div>
        <a href="index.php" class="btn-back" style="margin-bottom: 0.75rem;">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
        <h1 class="page-title" style="margin-top: 0.25rem;">
            Nhận xét: <?php echo htmlspecialchars($san['ten_san']); ?>
        </h1>
        <p class="page-subtitle">
            <i class="fas fa-map-marker-alt" style="margin-right: 4px; color: var(--primary);"></i>
            <?php echo htmlspecialchars($san['dia_chi'] ?? ''); ?>
        </p>
    </div>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); padding: 12px 18px; margin-bottom: 20px; color: #047857; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle" style="font-size: 1.1rem;"></i>
        <span>Đã xóa đánh giá thành công.</span>
    </div>
<?php endif; ?>

<!-- TỔNG QUAN SAO & PHÂN BỐ -->
<div class="overview-grid">
    <div>
        <div class="big-num"><?php echo number_format($san['avg_rating'], 1); ?></div>
        <div class="big-stars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
                <?php if ($s <= round($san['avg_rating'])): ?>
                    <i class="fas fa-star" style="color: #f59e0b;"></i>
                <?php else: ?>
                    <i class="far fa-star" style="color: #cbd5e1;"></i>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <div class="big-lbl"><?php echo number_format($san['total_reviews']); ?> lượt đánh giá</div>
    </div>
    <div class="bar-rows">
        <?php for ($s = 5; $s >= 1; $s--): ?>
            <?php $pct = $san['total_reviews'] > 0 ? round($star_dist[$s] / $san['total_reviews'] * 100) : 0; ?>
            <div class="bar-row">
                <span class="lbl"><?php echo $s; ?> <i class="fas fa-star"></i></span>
                <div class="bar-bg">
                    <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                </div>
                <span class="bar-cnt"><?php echo $star_dist[$s]; ?></span>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- BẢNG CHI TIẾT CÁC NHẬN XÉT -->
<div class="table-card">
    <div class="table-card-header">
        <h3 class="table-card-title">
            <i class="fas fa-comments" style="color: var(--primary); margin-right: 6px;"></i>
            Danh sách ý kiến &amp; phản hồi từ khách hàng (<?php echo $total; ?>)
        </h3>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>KHÁCH HÀNG</th>
                    <th style="width: 140px;">ĐÁNH GIÁ</th>
                    <th>NỘI DUNG NHẬN XÉT</th>
                    <th style="width: 140px;">NGÀY GỬI</th>
                    <th style="width: 70px; text-align: center;">XÓA</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $i => $rv): ?>
                    <tr>
                        <td style="color: var(--text-muted); font-weight: 600;">
                            <?php echo $offset + $i + 1; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="../../<?php echo htmlspecialchars($rv['avatar'] ?: 'images/avatar/default_avatar.svg'); ?>"
                                     style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0;"
                                     alt="Avatar"
                                     onerror="this.src='../../images/avatar/default_avatar.svg'">
                                <div>
                                    <div style="font-weight: 700; color: var(--text-heading); font-size: 0.92rem;">
                                        <?php echo htmlspecialchars($rv['ho_ten']); ?>
                                    </div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($rv['sdt'] ?? ''); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.95rem; display: flex; align-items: center; gap: 3px;" title="<?php echo $rv['so_sao']; ?>/5 sao">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <?php if ($s <= $rv['so_sao']): ?>
                                        <i class="fas fa-star" style="color: #f59e0b;"></i>
                                    <?php else: ?>
                                        <i class="far fa-star" style="color: #cbd5e1;"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td>
                            <div style="max-width: 400px; word-break: break-word; color: var(--text-body); font-size: 0.9rem; line-height: 1.45;">
                                <?php echo !empty($rv['noi_dung']) ? nl2br(htmlspecialchars($rv['noi_dung'])) : '<em style="color: var(--text-subtle);">Không có lời nhận xét</em>'; ?>
                            </div>
                        </td>
                        <td style="white-space: nowrap; color: var(--text-muted); font-size: 0.85rem;">
                            <?php echo date('d/m/Y H:i', strtotime($rv['ngay_tao'])); ?>
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đánh giá này không?');" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="delete_id" value="<?php echo $rv['danh_gia_id']; ?>">
                                <button type="submit" class="btn-action-danger" title="Xóa nhận xét" style="padding: 6px 10px; font-size: 0.85rem; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            Sân bóng này chưa có nhận xét nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div style="padding: 16px 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: center;">
            <div class="pagi">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?id=<?php echo $san_id; ?>&p=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
include '../footer_admin.php'; 
ob_end_flush();
?>
