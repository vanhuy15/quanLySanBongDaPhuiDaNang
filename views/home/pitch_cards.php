<?php
/**
 * View Partial: Pitch Cards
 * Rendered by HomeController for both static view and AJAX searches
 */
?>
<?php if (!empty($san_bongs) && count($san_bongs) > 0): ?>
    <?php foreach($san_bongs as $row): ?>
    <div class="san-card">
        <a href="index.php?controller=Home&action=detail&san_id=<?php echo $row['san_id']; ?>" style="display:block;position:relative;">
            <img src="images/san_bong/<?php echo $row['san_id']; ?>.jpg" alt="<?php echo htmlspecialchars($row['ten_san']); ?>" onerror="this.src='images/placeholder.png'">
        </a>
        
        <div class="pitch-card-body">
            <a href="index.php?controller=Home&action=detail&san_id=<?php echo $row['san_id']; ?>" style="text-decoration:none; color:inherit;">
                <h2 class="pitch-card-title"><?php echo htmlspecialchars($row['ten_san']); ?></h2>
            </a>

            <div class="pitch-card-rating-price">
                <?php if (!empty($row['total_reviews']) && $row['total_reviews'] > 0): ?>
                    <span class="pitch-rating-badge">
                        <i class="fas fa-star"></i> <?php echo number_format($row['avg_rating'], 1); ?> <span class="pitch-review-count">(<?php echo $row['total_reviews']; ?>)</span>
                    </span>
                <?php else: ?>
                    <span class="pitch-no-rating">Chưa có đánh giá</span>
                <?php endif; ?>

                <?php if (!empty($row['min_price']) && $row['min_price'] > 0): ?>
                    <span class="pitch-price-tag">
                        <?php echo number_format($row['min_price']); ?> đ+
                    </span>
                <?php endif; ?>
            </div>
            
            <?php
                $dia_chi_raw = $row['dia_chi'];
                $latitude = $row['latitude'];
                $longitude = $row['longitude'];
                if (!empty($latitude) && !empty($longitude)) {
                    $google_maps_link = "https://www.google.com/maps/search/?api=1&query=" . $latitude . "," . $longitude;
                } else {
                    $google_maps_link = "https://www.google.com/maps/search/?api=1&query=" . urlencode($dia_chi_raw);
                }
            ?>
            
            <div class="address-container">
                <p class="address-line">
                    <i class="fas fa-map-marker-alt" style="color: #ef4444; margin-top: 3px;"></i> 
                    <span><?php echo htmlspecialchars($row['dia_chi']); ?></span>
                </p>
    
                <a href="<?php echo $google_maps_link; ?>" target="_blank" class="btn-map-small" title="Xem vị trí trên bản đồ">
                    <img src="assets/images/google_maps_icon.svg" alt="Google Maps" style="width: 20px; height: 20px; object-fit: contain;">
                </a>
            </div>

            <div class="card-actions">
                <a href="index.php?controller=DatSan&action=book&san_id=<?php echo $row['san_id']; ?><?php echo (!empty($loai_san) && $loai_san == 'san_7') ? '&tab=san_7' : ''; ?>" class="btn-datsan">
                    Đặt sân
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="pitch-empty-state">
        <p class="pitch-empty-title">Không tìm thấy sân bóng phù hợp.</p>
        <p class="pitch-empty-subtitle">Vui lòng thử tìm kiếm theo từ khóa khác hoặc điều chỉnh bộ lọc.</p>
    </div>
<?php endif; ?>

