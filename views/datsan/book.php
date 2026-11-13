<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/menu.php'; ?>

<div class="datsan-layout">
    <div class="datsan-card">
        <h2 style="color: #0f172a; margin-top: 0; margin-bottom: 2rem; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-calendar-check" style="color: var(--primary-color);"></i> Đặt Sân Trực Tuyến
        </h2>
        <div id="message-container"></div>

        <form class="form-chon-lich" id="filterForm" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; display: flex; gap: 1.5rem; margin-bottom: 2rem;">
            <div class="form-group" style="flex: 1;">
                <label for="san_id" style="font-weight: 500; font-size: 0.9rem; margin-bottom: 8px;">Chọn loại sân:</label>
                <div class="select-wrapper">
                    <select name="san_id" id="san_id" style="width: 100%; padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #ffffff;">
                        <?php foreach ($san_bongs as $san) : ?>
                            <option value="<?php echo $san['san_id']; ?>" <?php echo ($san['san_id'] == $san_id_xem) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($san['ten_san']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label for="ngay_dat" style="font-weight: 500; font-size: 0.9rem; margin-bottom: 8px;">Chọn ngày xem lịch:</label>
                <div class="input-wrapper">
                    <input type="date" name="ngay_dat" id="ngay_dat" value="<?php echo $ngay_dat_xem; ?>" min="<?php echo max('2025-01-01', date('Y-m-d')); ?>" style="width: 100%; padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #ffffff;">
                </div>
            </div>
        </form>

        <div id="schedule-container">
            <!-- Content loaded via AJAX -->
            <div class="loading-state">
                <i class="fas fa-circle-notch fa-spin"></i>
                <p>Đang tải dữ liệu lịch sân...</p>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/datsan.js?v=1.0.1"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
