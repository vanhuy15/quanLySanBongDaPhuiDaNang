<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php';
require_admin();

$san_id = intval($_GET['san_id'] ?? 0);
if ($san_id <= 0) header('Location: index.php') and exit;

// Xử lý xóa đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM danh_gia WHERE danh_gia_id = ? AND san_id = ?");
    $stmt->bind_param("ii", $del_id, $san_id);
    $stmt->execute();
    $stmt->close();
    header("Location: detail.php?san_id=$san_id&deleted=1");
    exit;
}

// Thông tin sân + stats
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
if (!$san) { header('Location: index.php'); exit; }

// Phân phối sao
$dist_stmt = $conn->prepare("SELECT so_sao, COUNT(*) as cnt FROM danh_gia WHERE san_id = ? GROUP BY so_sao");
$dist_stmt->bind_param("i", $san_id);
$dist_stmt->execute();
$star_dist = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
$dr = $dist_stmt->get_result();
while ($row = $dr->fetch_assoc()) $star_dist[$row['so_sao']] = (int)$row['cnt'];
$dist_stmt->close();

// Danh sách đánh giá
$page = max(1, intval($_GET['p'] ?? 1));
$limit = 10; $offset = ($page - 1) * $limit;
$total = $san['total_reviews'];
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
?>

<div class="admin-module">
    <div class="dg-header">
        <div>
            <a href="index.php" class="btn-back">Quay lại</a>
            <h2 style="margin-top:0.5rem;">Đánh giá: <?php echo htmlspecialchars($san['ten_san']); ?></h2>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert-ok"><i class="fas fa-check-circle"></i> Đã xóa đánh giá thành công.</div>
    <?php endif; ?>

    <!-- Tổng quan sao -->
    <?php if ($san['total_reviews'] > 0): ?>
    <div class="overview-grid">
        <div>
            <div class="big-num"><?php echo $san['avg_rating']; ?></div>
            <div class="big-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="<?php echo $s <= round($san['avg_rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                <?php endfor; ?>
            </div>
            <div class="big-lbl"><?php echo $san['total_reviews']; ?> đánh giá</div>
        </div>
        <div class="bar-rows">
            <?php for ($s = 5; $s >= 1; $s--): ?>
            <?php $pct = $san['total_reviews'] > 0 ? round($star_dist[$s] / $san['total_reviews'] * 100) : 0; ?>
            <div class="bar-row">
                <span class="lbl"><?php echo $s; ?> <i class="fas fa-star"></i></span>
                <div class="bar-bg"><div class="bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                <span class="bar-cnt"><?php echo $star_dist[$s]; ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bảng đánh giá -->
    <?php if (count($reviews) > 0): ?>
    <table class="rv-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Người dùng</th>
                <th>Sao</th>
                <th>Nội dung</th>
                <th>Ngày</th>
                <th>Xóa</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $i => $rv): ?>
            <tr>
                <td style="color:#9ca3af;"><?php echo $offset + $i + 1; ?></td>
                <td>
                    <div class="user-cell">
                        <img src="../../<?php echo htmlspecialchars($rv['avatar'] ?: 'images/avatar/default_avatar.svg'); ?>"
                             class="user-av" alt="av"
                             onerror="this.src='../../images/avatar/default_avatar.svg'">
                        <div>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($rv['ho_ten']); ?></div>
                            <div style="font-size:0.78rem;color:#9ca3af;"><?php echo htmlspecialchars($rv['sdt'] ?? ''); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="stars-row">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="<?php echo $s <= $rv['so_sao'] ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </span>
                </td>
                <td>
                    <span class="rv-text" title="<?php echo htmlspecialchars($rv['noi_dung']); ?>">
                        <?php echo !empty($rv['noi_dung']) ? htmlspecialchars($rv['noi_dung']) : '<em style="color:#ccc">Không có</em>'; ?>
                    </span>
                </td>
                <td style="white-space:nowrap;color:#64748b;">
                    <?php echo date('d/m/Y', strtotime($rv['ngay_tao'])); ?>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Xóa đánh giá này?');" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $rv['danh_gia_id']; ?>">
                        <button type="submit" class="btn-delete" style="padding: 6px 10px; font-size: 0.85rem;"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <div class="pagi">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?san_id=<?php echo $san_id; ?>&p=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p style="text-align:center;color:#9ca3af;padding:2rem;">Sân này chưa có đánh giá nào.</p>
    <?php endif; ?>
</div>

<?php include '../footer_admin.php'; ?>
