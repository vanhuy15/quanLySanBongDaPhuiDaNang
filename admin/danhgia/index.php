<?php
session_start();
include '../../includes/db.php';
include '../header_admin.php';
require_admin();

// Phân trang
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số sân để chia trang
$total_san_row = $conn->query("SELECT COUNT(san_id) as cnt FROM san_bong")->fetch_assoc();
$total_san = $total_san_row['cnt'];
$total_pages = ceil($total_san / $limit);

// Lấy tổng thống kê
$stat_row = $conn->query("SELECT COUNT(danh_gia_id) as total_rv, SUM(CASE WHEN so_sao <= 2 THEN 1 ELSE 0 END) as bad_rv FROM danh_gia")->fetch_assoc();
$total_reviews_all = $stat_row['total_rv'] ?? 0;
$bad_reviews_all = $stat_row['bad_rv'] ?? 0;

// Tổng quan đánh giá theo từng sân
$result = $conn->query("
    SELECT sb.san_id, sb.ten_san, sb.dia_chi,
           COALESCE(ROUND(AVG(dg.so_sao), 1), 0) as avg_rating,
           COUNT(dg.danh_gia_id) as total_reviews,
           SUM(CASE WHEN dg.so_sao <= 2 THEN 1 ELSE 0 END) as bad_reviews
    FROM san_bong sb
    LEFT JOIN danh_gia dg ON dg.san_id = sb.san_id
    GROUP BY sb.san_id, sb.ten_san, sb.dia_chi
    ORDER BY total_reviews DESC
    LIMIT $limit OFFSET $offset
");
$san_list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý Đánh giá &amp; Phản hồi</h1>
        <p class="page-subtitle">Theo dõi chất lượng dịch vụ và mức độ hài lòng của khách hàng.</p>
    </div>
</div>

<!-- 3 STAT CARDS -->
<div class="stats-grid-3">
    <div class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-teal"><i class="fas fa-comments"></i></div>
            <span class="stat-tag tag-blue">Tổng cộng</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Tổng lượt đánh giá</h4>
            <div class="stat-number"><?php echo number_format($total_reviews_all); ?></div>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-green"><i class="fas fa-futbol"></i></div>
            <span class="stat-tag tag-green">Khảo sát</span>
        </div>
        <div class="stat-card-bottom">
            <h4>Số lượng sân</h4>
            <div class="stat-number"><?php echo count($san_list); ?></div>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-red"><i class="fas fa-exclamation-triangle"></i></div>
            <?php if ($bad_reviews_all > 0): ?>
                <span class="stat-tag tag-red">Cần cải thiện</span>
            <?php else: ?>
                <span class="stat-tag tag-green">Tốt</span>
            <?php endif; ?>
        </div>
        <div class="stat-card-bottom">
            <h4>Đánh giá xấu (1-2⭐)</h4>
            <div class="stat-number" style="color: #dc2626;"><?php echo number_format($bad_reviews_all); ?></div>
        </div>
    </div>
</div>

<!-- DATA TABLE CARD -->
<div class="table-card">
    <div class="table-card-header">
        <h3 class="table-card-title">Chỉ số đánh giá theo từng sân bóng</h3>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>SÂN BÓNG</th>
                    <th>ĐÁNH GIÁ TRUNG BÌNH</th>
                    <th>SỐ LƯỢT ĐÁNH GIÁ</th>
                    <th>TRẠNG THÁI CHẤT LƯỢNG</th>
                    <th style="text-align: center;">CHI TIẾT</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($san_list)): ?>
                    <?php foreach ($san_list as $san): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--text-heading); font-size: 0.95rem;"><?php echo htmlspecialchars($san['ten_san']); ?></strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($san['dia_chi']); ?>
                            </div>
                        </td>

                        <td>
                            <?php if ($san['total_reviews'] > 0): ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <strong style="font-size: 1.1rem; color: var(--text-heading);"><?php echo number_format($san['avg_rating'], 1); ?></strong>
                                    <span style="color: #f59e0b; font-size: 0.9rem;">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="<?php echo $s <= round($san['avg_rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--text-subtle); font-style: italic;">Chưa có đánh giá</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span style="font-weight: 700; color: var(--text-heading);"><?php echo $san['total_reviews']; ?></span> lượt
                        </td>

                        <td>
                            <?php if ($san['total_reviews'] == 0): ?>
                                <span style="color: var(--text-subtle);">-</span>
                            <?php elseif ($san['bad_reviews'] > 0): ?>
                                <span class="badge badge-huy">
                                    <span class="badge-dot"></span> Có <?php echo $san['bad_reviews']; ?> phản hồi xấu
                                </span>
                            <?php else: ?>
                                <span class="badge badge-xacnhan">
                                    <span class="badge-dot"></span> Đánh giá tích cực
                                </span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align: center;">
                            <?php if ((int)$san['total_reviews'] > 0): ?>
                                <a href="detail.php?id=<?php echo $san['san_id']; ?>" class="btn-primary-modern" style="padding: 6px 14px; font-size: 0.82rem;">
                                    Xem nhận xét
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-subtle); font-style: italic; font-size: 0.85rem;">Chưa có nhận xét</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">
                            Chưa có dữ liệu đánh giá sân bóng.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1) : ?>
        <div style="padding: 16px 24px; border-top: 1px solid var(--border-light); display: flex; justify-content: flex-end; gap: 6px;">
            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                <a href="index.php?p=<?php echo $i; ?>" 
                   style="padding: 6px 12px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid <?php echo ($i == $page) ? 'var(--primary)' : 'var(--border)'; ?>; background: <?php echo ($i == $page) ? 'var(--primary)' : '#ffffff'; ?>; color: <?php echo ($i == $page) ? '#ffffff' : 'var(--text-body)'; ?>;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer_admin.php'; ?>
