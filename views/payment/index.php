<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/menu.php'; ?>

<div class="thanhtoan-container">
    <h2>Thanh toán - <?php echo (stripos($dat_san['ten_san'] ?? '', 'sân') === 0) ? htmlspecialchars($dat_san['ten_san']) : 'Sân ' . htmlspecialchars($dat_san['ten_san'] ?? ''); ?></h2>
    
    <?php if ($dat_san['trang_thai'] === 'Đã hủy'): ?>
        <div class="payment-expired-card">
            <h3>Đơn đặt sân đã hết hạn hoặc bị hủy</h3>
            <p>Đơn đặt sân #<?php echo $dat_san_id; ?> đã quá thời gian giữ chỗ 10 phút chưa thanh toán. Sân đã được tự động mở lại.</p>
            <a href="index.php?controller=DatSan&action=book<?php echo !empty($dat_san['san_id']) ? '&san_id=' . (int)$dat_san['san_id'] : ''; ?>" class="btn-action-primary">
                Đặt lại sân bóng
            </a>
        </div>
    <?php else: ?>
        <!-- 10-Minute Countdown Banner -->
        <?php if(!empty($error)): ?>
            <div class="error" style="margin-bottom: 20px;">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if(!$existing_payment): ?>
        <div class="payment-countdown">
            <span>Thời gian giữ chỗ còn lại: <strong id="countdownTimer" data-remaining-seconds="<?php echo max(0, (int)($remaining_seconds ?? 0)); ?>" data-cash-locked="<?php echo (!empty($is_cash_locked) && $is_cash_locked) ? '1' : '0'; ?>">--:--</strong></span>
        </div>
        <?php endif; ?>

        <div class="thongtin-datsan">
            <div class="booking-info-row">
                <span class="info-label">Ngày đặt:</span>
                <span class="info-value"><?php echo date("d/m/Y", strtotime($dat_san['ngay_dat'])); ?></span>
            </div>
            <div class="booking-info-row">
                <span class="info-label">Thời gian:</span>
                <span class="info-value"><?php echo date("H:i", strtotime($dat_san['gio_bat_dau']))." - ".date("H:i", strtotime($dat_san['gio_ket_thuc'])); ?></span>
            </div>
            <div class="booking-info-row">
                <span class="info-label">Giá tiền:</span>
                <span class="price-highlight"><?php echo number_format($dat_san['gia_tien']); ?> VNĐ</span>
            </div>
        </div>

        <?php if(!$existing_payment): ?>
            <?php 
                $user_diem_uy_tin = (int)($dat_san['diem_uy_tin'] ?? 100);
                $is_cash_locked = ($user_diem_uy_tin < 60);
            ?>
            <form method="POST" id="formThanhtoan">
            <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
                <h3 class="payment-method-title">Chọn phương thức thanh toán:</h3>
                
                <div class="payment-methods">
                    <?php if ($is_cash_locked): ?>
                        <div class="payment-card locked" title="Khóa do điểm uy tín dưới 60">
                            <div class="payment-card-content">
                                <i class="fas fa-ban payment-card-icon"></i>
                                <strong class="payment-card-title" style="color: #991b1b;">Tiền mặt</strong>
                                <span class="payment-card-sub warn">Đã khóa (Uy tín: <?php echo $user_diem_uy_tin; ?>đ)</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <label class="payment-card">
                            <input type="radio" name="phuong_thuc" value="tien_mat" onchange="toggleQR()" required style="position: absolute; opacity: 0;">
                            <div class="payment-card-content">
                                <i class="fas fa-money-bill-wave payment-card-icon"></i>
                                <strong class="payment-card-title">Tiền mặt</strong>
                            </div>
                        </label>
                    <?php endif; ?>
                    
                    <label class="payment-card">
                        <input type="radio" name="phuong_thuc" value="chuyen_khoan" <?php echo $is_cash_locked ? 'checked' : ''; ?> onchange="toggleQR()" required style="position: absolute; opacity: 0;">
                        <div class="payment-card-content">
                            <i class="fas fa-qrcode payment-card-icon"></i>
                            <strong class="payment-card-title">QR chuyển khoản</strong>
                        </div>
                    </label>
                </div>

                <div id="chuyenKhoanBox" style="display:none; margin-top:20px;">
                    <h3 style="text-align: center; color: #1e293b; margin-bottom: 1rem; font-size: 1.15rem; font-weight: 700;">
                        Quét mã VietQR để thanh toán
                    </h3>
                    <?php
                        $bank_id = getenv('VIETQR_BANK_ID') ?: "MB";
                        $bank_name = getenv('VIETQR_BANK_NAME') ?: "MB Bank (Ngân hàng Quân Đội)";
                        $stk = getenv('VIETQR_ACCOUNT_NO') ?: "1111000050174";
                        $chu_tk = getenv('VIETQR_ACCOUNT_NAME') ?: "HOANG VAN HUY";
                        $noidung_ck = "DATSAN " . $dat_san_id;
                        $so_tien_ck = (int)$dat_san['gia_tien'];
                        
                        // Sử dụng template qr_only.svg: Chỉ hiển thị duy nhất mã QR, không có chữ thừa bên dưới
                        $qr_url = "https://img.vietqr.io/image/" . $bank_id . "-" . $stk . "-qr_only.svg";
                        $qr_url .= "?amount=" . $so_tien_ck;
                        $qr_url .= "&addInfo=" . urlencode($noidung_ck);
                        $qr_url .= "&accountName=" . urlencode($chu_tk);
                    ?>
                    <div class="qr-container">
                        <div class="qr-frame">
                            <img src="<?php echo $qr_url; ?>" alt="Mã QR Chuyển khoản" class="qr-image" loading="eager" crossorigin="anonymous">
                        </div>
                        <p class="qr-instruction">
                            Mở ứng dụng Ngân hàng bất kỳ để quét mã thanh toán tự động
                        </p>

                        <!-- Thông tin chuyển khoản chi tiết nếu khách không quét được QR -->
                        <div class="qr-detail-box">
                            <div class="qr-detail-row">
                                <span class="lbl">Ngân hàng:</span>
                                <strong class="val"><?php echo htmlspecialchars($bank_name); ?></strong>
                            </div>
                            <div class="qr-detail-row">
                                <span class="lbl">Số tài khoản:</span>
                                <div class="qr-val-with-action">
                                    <strong id="stkText" class="val val-stk"><?php echo htmlspecialchars($stk); ?></strong>
                                    <button type="button" class="qr-copy-btn" onclick="copyText('stkText', this)" title="Sao chép số tài khoản">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="qr-detail-row">
                                <span class="lbl">Chủ tài khoản:</span>
                                <strong class="val" style="text-transform: uppercase;"><?php echo htmlspecialchars($chu_tk); ?></strong>
                            </div>
                            <div class="qr-detail-row">
                                <span class="lbl">Số tiền:</span>
                                <strong class="val val-amount"><?php echo number_format($so_tien_ck); ?> VNĐ</strong>
                            </div>
                            <div class="qr-detail-row">
                                <span class="lbl">Nội dung CK:</span>
                                <div class="qr-val-with-action">
                                    <strong id="noiDungText" class="val val-noidung"><?php echo htmlspecialchars($noidung_ck); ?></strong>
                                    <button type="button" class="qr-copy-btn" onclick="copyText('noiDungText', this)" title="Sao chép nội dung chuyển khoản">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="submitBtnWrapper" style="<?php echo $is_cash_locked ? 'display:block;' : 'display:none;'; ?> text-align: center; margin-top: 2rem;">
                    <button type="submit" id="btnSubmitPayment" class="btn-submit" style="background: <?php echo $is_cash_locked ? '#0284c7' : '#0d9488'; ?>;">
                        <span id="submitBtnText"><?php echo $is_cash_locked ? 'Xác nhận chuyển khoản' : 'Xác nhận đặt sân'; ?></span>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="payment-success-card">
                <h3>
                    <?php if ($existing_payment['phuong_thuc'] == 'chuyen_khoan'): ?>
                        Thanh toán &amp; Chốt sân thành công!
                    <?php else: ?>
                        Giữ chỗ sân thành công!
                    <?php endif; ?>
                </h3>
                <p>
                    Phương thức: 
                    <strong style="color: #0f172a;">
                        <?php 
                            if ($existing_payment['phuong_thuc'] == 'chuyen_khoan') echo 'QR chuyển khoản (Đã xác nhận)';
                            else echo 'Tiền mặt (Thanh toán khi đá xong)';
                        ?>
                    </strong>
                </p>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 25px;">
                    Thông tin đơn đặt sân và hóa đơn đã được cập nhật vào tài khoản của bạn.
                </p>
                <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <a href="index.php?controller=User&action=history" class="btn-action-primary">
                        Xem lịch sử đặt sân
                    </a>
                    <a href="index.php" class="btn-action-outline">
                        Về trang chủ
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="assets/js/payment.js?v=1.0.1"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
