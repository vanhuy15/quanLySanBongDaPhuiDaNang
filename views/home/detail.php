<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/menu.php'; ?>

<?php
$avg        = round($san['avg_rating'], 1);
$total_rv   = (int)$san['total_reviews'];
$loai_label = match($san['loai_san'] ?? '') {
    'san_5_7' => 'Ghép 5 & 7', 'san_7' => 'Sân 7', default => 'Sân 5',
};
if (!empty($san['latitude']) && !empty($san['longitude'])) {
    $maps_link = "https://www.google.com/maps/search/?api=1&query={$san['latitude']},{$san['longitude']}";
} else {
    $maps_link = "https://www.google.com/maps/search/?api=1&query=" . urlencode($san['dia_chi']);
}
function renderStars(float $n, float $size = 1.0): string {
    $html = '<span style="color:#f59e0b;font-size:' . $size . 'rem;">';
    for ($s = 1; $s <= 5; $s++) $html .= '<i class="' . ($s <= round($n) ? 'fas' : 'far') . ' fa-star"></i>';
    return $html . '</span>';
}
?>

<div class="pd-wrap">

    <!-- Breadcrumb -->
    <div class="pd-breadcrumb">
        <a href="index.php">Trang chủ</a>
        <span>/</span>
        <span><?php echo htmlspecialchars($san['ten_san']); ?></span>
    </div>

    <!-- Page Title (above both columns) -->
    <div class="pd-title-row">
        <div>
            <h1 class="pd-title"><?php echo htmlspecialchars($san['ten_san']); ?></h1>
            <div class="pd-subtitle">
                <span class="pd-type-chip"><?php echo $loai_label; ?></span>
                <?php if ($total_rv > 0): ?>
                <div class="pd-rating-inline">
                    <?php echo renderStars($avg, 0.85); ?>
                    <span class="avg"><?php echo number_format($avg, 1); ?></span>
                    <span class="cnt">(<?php echo $total_rv; ?> đánh giá)</span>
                </div>
                <?php else: ?>
                <span style="font-size:0.83rem;color:#94a3b8;"><i class="far fa-star"></i> Chưa có đánh giá</span>
                <?php endif; ?>
                <span class="pd-subtitle-item"><i class="fas fa-map-marker-alt" style="color:#ef4444;"></i> <?php echo htmlspecialchars($san['dia_chi']); ?></span>
            </div>
        </div>
    </div>

    <!-- HERO: Ảnh + Booking card -->
    <div class="pd-hero">
        <div class="pd-img">
            <img src="images/san_bong/<?php echo $san['san_id']; ?>.jpg"
                 alt="<?php echo htmlspecialchars($san['ten_san']); ?>"
                 onerror="this.src='images/placeholder.png'">
        </div>

        <div class="pd-booking-card">
            <!-- Price -->
            <div class="pd-price-block">
                <span class="from-label">Giá từ</span>
                <div class="price-main">
                    <?php if ($san['min_price'] > 0): ?>
                        <?php echo number_format($san['min_price']); ?><span>đ</span>
                    <?php else: ?>
                        <span style="font-size:1rem;color:#94a3b8;">Liên hệ</span>
                    <?php endif; ?>
                </div>
                <?php if ($san['max_price'] > $san['min_price']): ?>
                <div class="price-range">đến <?php echo number_format($san['max_price']); ?>đ / giờ</div>
                <?php else: ?>
                <div class="price-range">/ giờ</div>
                <?php endif; ?>
            </div>

            <hr class="pd-divider">

            <!-- Meta info -->
            <div class="pd-meta-list">
                <div class="pd-meta-item">
                    <i class="fas fa-th-large"></i>
                    <span><?php echo $san['so_luong']; ?> sân con · <strong><?php echo $loai_label; ?></strong></span>
                </div>
                <div class="pd-meta-item">
                    <i class="fas fa-map-marker-alt" style="color:#ef4444;"></i>
                    <span><?php echo htmlspecialchars($san['dia_chi']); ?></span>
                </div>
                <?php if (!empty($san['so_dien_thoai'])): ?>
                <div class="pd-meta-item">
                    <i class="fas fa-phone"></i>
                    <span><?php echo htmlspecialchars($san['so_dien_thoai']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <hr class="pd-divider">

            <!-- CTAs -->
            <a href="index.php?controller=DatSan&action=book&san_id=<?php echo $san['san_id']; ?>" class="btn-book-primary">
                Đặt sân ngay
            </a>
            <a href="<?php echo $maps_link; ?>" target="_blank" class="btn-maps-outline">
                <img src="assets/images/google_maps_icon.svg" style="width:18px;height:18px;object-fit:contain;" alt="Google Maps">
                Xem trên Google Maps
            </a>
        </div>
    </div>

    <!-- CONTENT: Left sections + Right sidebar -->
    <div class="pd-content">
        <div class="pd-left">

            <!-- Khung giờ -->
            <div class="pd-section">
                <div class="pd-section-title">
                    Khung giờ & Giá
                </div>
                <?php if (count($khung_gios) > 0): ?>
                <table class="gio-table">
                    <tbody>
                        <?php foreach ($khung_gios as $kg): ?>
                        <tr>
                            <td class="time-col">
                                <?php echo date('H:i', strtotime($kg['gio_bat_dau'])) . ' — ' . date('H:i', strtotime($kg['gio_ket_thuc'])); ?>
                            </td>
                            <td class="price-col">
                                <?php if ($san['loai_san'] !== 'san_7' && !empty($kg['gia_tien']) && $kg['gia_tien'] > 0): ?>
                                    <?php echo number_format($kg['gia_tien']); ?>đ
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-weight:600;">—</span>
                                <?php endif; ?>
                                <div style="font-size:0.75rem;font-weight:400;color:#94a3b8;">Sân 5</div>
                            </td>
                            <td class="price7-col">
                                <?php if ($san['loai_san'] !== 'san_5' && !empty($kg['gia_tien_san_7']) && $kg['gia_tien_san_7'] > 0): ?>
                                    <?php echo number_format($kg['gia_tien_san_7']); ?>đ
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-weight:600;">—</span>
                                <?php endif; ?>
                                <div style="font-size:0.75rem;font-weight:400;color:#94a3b8;">Sân 7</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:#94a3b8;text-align:center;padding:1rem 0;font-size:0.9rem;">Chưa có thông tin khung giờ.</p>
                <?php endif; ?>
            </div>

            <!-- Đánh giá -->
            <div class="pd-section">
                <div class="pd-section-title">
                    Đánh giá từ khách hàng
                    <?php if ($total_rv > 0): ?>
                    <span style="margin-left:auto;font-size:0.82rem;font-weight:500;color:#94a3b8;"><?php echo $total_rv; ?> đánh giá</span>
                    <?php endif; ?>
                </div>

                <?php if ($total_rv > 0): ?>
                <!-- Rating overview -->
                <div class="pd-rating-grid">
                    <div class="pd-big-score">
                        <div class="num"><?php echo number_format($avg, 1); ?></div>
                        <?php echo renderStars($avg, 1.05); ?>
                        <div class="lbl"><?php echo $total_rv; ?> lượt</div>
                    </div>
                    <div class="bar-group">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                        <?php $pct = $total_rv > 0 ? round($star_dist[$s] / $total_rv * 100) : 0; ?>
                        <div class="bar-item">
                            <span class="lbl"><?php echo $s; ?> <i class="fas fa-star"></i></span>
                            <div class="bar-track"><div class="bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                            <span class="cnt"><?php echo $star_dist[$s]; ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Review list -->
                <?php foreach ($reviews as $rv): ?>
                <?php $av = !empty($rv['avatar']) ? $rv['avatar'] : 'images/avatar/default_avatar.svg'; ?>
                <div class="rv-item">
                    <img src="<?php echo htmlspecialchars($av); ?>"
                         class="rv-av" alt="avatar"
                         onerror="this.src='images/avatar/default_avatar.svg'">
                    <div class="rv-body">
                        <div class="rv-top">
                            <span class="rv-name"><?php echo htmlspecialchars($rv['ho_ten']); ?></span>
                            <span class="rv-date"><?php echo date('d/m/Y', strtotime($rv['ngay_tao'])); ?></span>
                        </div>
                        <div class="rv-stars"><?php echo renderStars((float)$rv['so_sao'], 0.82); ?></div>
                        <?php if (!empty($rv['noi_dung'])): ?>
                        <p class="rv-text"><?php echo nl2br(htmlspecialchars($rv['noi_dung'])); ?></p>
                        <?php else: ?>
                        <p class="rv-text" style="font-style:italic;color:#cbd5e1;">Không có nhận xét.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Phân trang -->
                <?php if ($total_review_pages > 1): ?>
                <div class="pd-pagi">
                    <?php if ($review_page > 1): ?>
                        <a href="?controller=Home&action=detail&san_id=<?php echo $san['san_id']; ?>&review_page=<?php echo $review_page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_review_pages; $i++): ?>
                        <a href="?controller=Home&action=detail&san_id=<?php echo $san['san_id']; ?>&review_page=<?php echo $i; ?>"
                           class="<?php echo $i == $review_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($review_page < $total_review_pages): ?>
                        <a href="?controller=Home&action=detail&san_id=<?php echo $san['san_id']; ?>&review_page=<?php echo $review_page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="rv-empty">
                    <i class="far fa-comment-dots"></i>
                    <p>Chưa có đánh giá nào cho sân này.</p>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /.pd-left -->

        <!-- Right sidebar (desktop) -->
        <div class="pd-right">
            <div class="pd-sidebar-card">
                <div class="pd-sidebar-title">Thông tin sân</div>
                <div class="pd-sidebar-fact">
                    <span class="key">Loại sân</span>
                    <span class="val"><?php echo $loai_label; ?></span>
                </div>
                <div class="pd-sidebar-fact">
                    <span class="key">Số sân con</span>
                    <span class="val"><?php echo $san['so_luong']; ?> sân</span>
                </div>
                <div class="pd-sidebar-fact">
                    <span class="key">Xếp hạng</span>
                    <span class="val" style="color:#f59e0b;">
                        <?php echo $total_rv > 0 ? '⭐ ' . number_format($avg, 1) : '—'; ?>
                    </span>
                </div>
                <div class="pd-sidebar-fact">
                    <span class="key">Khung giờ</span>
                    <span class="val"><?php echo count($khung_gios); ?> ca/ngày</span>
                </div>
                <?php if ($san['min_price'] > 0): ?>
                <div class="pd-sidebar-fact">
                    <span class="key">Giá thấp nhất</span>
                    <span class="val" style="color:var(--primary-color);"><?php echo number_format($san['min_price']); ?>đ</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.pd-content -->

</div><!-- /.pd-wrap -->

<?php include __DIR__ . '/../../includes/footer.php'; ?>
