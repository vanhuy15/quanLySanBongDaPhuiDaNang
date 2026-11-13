<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/menu.php'; ?>

<?php
    $userAvatar = !empty($user['avatar']) ? $user['avatar'] : 'images/avatar/default_avatar.svg';
    $score = (int)($user['diem_uy_tin'] ?? 100);
    $penalty_count = (int)($user['so_lan_bung_san'] ?? 0);
    
    if ($score >= 80) {
        $tier_name = "Uy Tín Xuất Sắc";
        $tier_bg = "#ecfdf5";
        $tier_border = "#a7f3d0";
        $tier_color = "#047857";
        $tier_bar_color = "#10b981";
        $tier_desc = "Tài khoản VIP uy tín! Bạn được tự do đặt sân bằng cả Tiền mặt và Chuyển khoản VietQR.";
    } elseif ($score >= 60) {
        $tier_name = "Uy Tín Tiêu Chuẩn";
        $tier_bg = "#eff6ff";
        $tier_border = "#bfdbfe";
        $tier_color = "#1d4ed8";
        $tier_bar_color = "#3b82f6";
        $tier_desc = "Tài khoản ở trạng thái tốt. Tiếp tục duy trì để nâng hạng lên Uy Tín Xuất Sắc!";
    } elseif ($score >= 40) {
        $tier_name = "Cảnh Báo Uy Tín";
        $tier_bg = "#fffbeb";
        $tier_border = "#fde68a";
        $tier_color = "#b45309";
        $tier_bar_color = "#f59e0b";
        $tier_desc = "Điểm uy tín dưới 60đ (do bùng sân). Hệ thống tạm khóa thanh toán Tiền mặt, bắt buộc Chuyển khoản VietQR.";
    } else {
        $tier_name = "Danh Sách Đen (Bị Khóa)";
        $tier_bg = "#fef2f2";
        $tier_border = "#fca5a5";
        $tier_color = "#b91c1c";
        $tier_bar_color = "#ef4444";
        $tier_desc = "Điểm uy tín dưới 40đ. Tài khoản của bạn đã bị TẠM KHÓA QUYỀN ĐẶT SÂN. Vui lòng liên hệ ban quản lý.";
    }
?>

<div class="profile-page-wrapper">
    
    <!-- User Profile Header Banner -->
    <div class="profile-banner">
        <!-- Interactive Avatar Container -->
        <div class="avatar-upload-wrapper" id="avatarUploadContainer" title="Nhấp để thay đổi ảnh đại diện">
            <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Avatar" id="userProfileAvatarImg" class="user-main-avatar" onerror="this.src='images/avatar/default_avatar.svg'">
            <div class="avatar-overlay">
                <i class="fas fa-camera"></i>
                <span>Đổi ảnh</span>
            </div>
            <div class="avatar-spinner" id="avatarSpinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <input type="file" id="avatarFileInput" accept="image/png, image/jpeg, image/jpg, image/webp" style="display: none;">
        </div>

        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['ho_ten']); ?></h1>
            <p>
                <span>@<?php echo htmlspecialchars($user['ten_dang_nhap']); ?></span>
                <span>•</span>
                <span><?php echo htmlspecialchars($user['sdt'] ?? ''); ?></span>
                <span>•</span>
                <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
            </p>
        </div>
    </div>

    <!-- Alert Messages (AJAX & Server) -->
    <div id="ajaxAlertContainer"></div>

    <!-- 4 Quick Stats Bar -->
    <div class="profile-stats-grid">
        <div class="profile-stat-box">
            <span class="stat-label">Điểm uy tín</span>
            <span class="stat-value" style="color: <?php echo $tier_color; ?>;"><?php echo $score; ?>/100</span>
        </div>
        <div class="profile-stat-box">
            <span class="stat-label">Tổng lượt đặt</span>
            <span class="stat-value"><?php echo (int)($user_stats['total_bookings'] ?? 0); ?></span>
        </div>
        <div class="profile-stat-box">
            <span class="stat-label">Trận hoàn thành</span>
            <span class="stat-value" style="color: #059669;"><?php echo (int)($user_stats['completed_bookings'] ?? 0); ?></span>
        </div>
        <div class="profile-stat-box">
            <span class="stat-label">Đơn đã hủy</span>
            <span class="stat-value" style="color: #dc2626;"><?php echo (int)($user_stats['cancelled_bookings'] ?? 0); ?></span>
        </div>
    </div>

    <!-- Reputation Score Tier Card -->
    <div class="tier-card" style="background: <?php echo $tier_bg; ?>; border: 1.5px solid <?php echo $tier_border; ?>;">
        <div class="tier-header">
            <div>
                <h3 class="tier-title" style="color: <?php echo $tier_color; ?>;">
                    Hạng: <?php echo $tier_name; ?>
                </h3>
                <p class="tier-desc">
                    <?php echo $tier_desc; ?>
                </p>
            </div>
            
            <div class="tier-score-container" style="display: flex; flex-direction: column; align-items: flex-end;">
                <?php if ($penalty_count > 0 && $score < 80): ?>
                    <div class="tier-penalty" style="margin-bottom: 6px;">
                        Từng bùng sân: <?php echo $penalty_count; ?> lần
                    </div>
                <?php endif; ?>

                <button type="button" onclick="openUserReputationModal()" style="background: #ffffff; color: <?php echo $tier_color; ?>; border: 1.5px solid <?php echo $tier_border; ?>; border-radius: 8px; padding: 6px 14px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s;">
                    Xem lịch sử tín dụng
                </button>
            </div>
        </div>
        <!-- Progress Bar -->
        <div class="tier-progress-bg">
            <div class="tier-progress-bar" style="background: <?php echo $tier_bar_color; ?>; width: <?php echo max(5, $score); ?>%;"></div>
        </div>
    </div>

    <!-- Main Content: Two Distinct Form Cards -->
    <div class="profile-forms-grid">
        
        <!-- CARD 1: Cập nhật thông tin cá nhân -->
        <div class="profile-card-box">
            <div class="profile-card-header">
                <h2>Thông tin cá nhân</h2>
                <p>Cập nhật họ tên, số điện thoại và email nhận hóa đơn</p>
            </div>

            <?php if(!empty($info_success)): ?>
                <div class="success-box" style="margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($info_success); ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($info_error)): ?>
                <div class="error" style="margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($info_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?controller=User&action=profile" id="infoForm">
                <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
                <input type="hidden" name="update_profile_info" value="1">

                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['ten_dang_nhap']); ?>" disabled>
                    <small style="color: #94a3b8; font-size: 0.78rem; margin-top: 4px; display: block;">Tên đăng nhập cố định không thể thay đổi</small>
                </div>

                <div class="form-group">
                    <label>Họ và tên <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="ho_ten" value="<?php echo htmlspecialchars($user['ho_ten']); ?>" required placeholder="Ví dụ: Hoàng Văn Huy">
                </div>

                <div class="form-group">
                    <label>Số điện thoại <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="sdt" id="sdtInput" value="<?php echo htmlspecialchars($user['sdt'] ?? ''); ?>" required pattern="[0-9]{10}" placeholder="Nhập 10 chữ số">
                </div>

                <div class="form-group">
                    <label>Email liên hệ & hóa đơn <span style="color: #ef4444;">*</span></label>
                    <input type="email" name="email" id="emailInput" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required placeholder="Ví dụ: name@gmail.com">
                </div>

                <div class="profile-form-footer">
                    <button type="submit" class="btn-save">
                        Lưu thông tin cá nhân
                    </button>
                </div>
            </form>
        </div>

        <!-- CARD 2: Bảo mật & Đổi mật khẩu -->
        <div class="profile-card-box">
            <div class="profile-card-header">
                <h2>Đổi mật khẩu</h2>
                <p>Bảo vệ tài khoản bằng mật khẩu mạnh ít nhất 6 ký tự</p>
            </div>

            <?php if(!empty($password_success)): ?>
                <div class="success-box" style="margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($password_success); ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($password_error)): ?>
                <div class="error" style="margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($password_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?controller=User&action=profile" id="passwordForm">
                <?php echo function_exists("csrf_field") ? csrf_field() : ""; ?>
                <input type="hidden" name="change_password" value="1">

                <div class="form-group">
                    <label>Mật khẩu hiện tại <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="mk_cu" id="mk_cu" required placeholder="Nhập mật khẩu đang dùng" style="padding-right: 42px;">
                        <button type="button" onclick="togglePasswordVisibility('mk_cu', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.05rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mật khẩu mới <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="mk_moi" id="mk_moi" required placeholder="Tối thiểu 6 ký tự" style="padding-right: 42px;">
                        <button type="button" onclick="togglePasswordVisibility('mk_moi', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.05rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Xác nhận mật khẩu mới <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="mk_xac_nhan" id="mk_xac_nhan" required placeholder="Nhập lại mật khẩu mới" style="padding-right: 42px;">
                        <button type="button" onclick="togglePasswordVisibility('mk_xac_nhan', this)" aria-label="Hiện mật khẩu" tabindex="-1" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.05rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="profile-form-footer">
                    <button type="submit" class="btn-save">
                        Cập nhật mật khẩu
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- MODAL XEM LỊCH SỬ TÍN DỤNG USER -->
    <div id="userReputationModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: #ffffff; width: 95%; max-width: 820px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15); overflow: hidden; margin: 20px; animation: modalFadeIn 0.2s ease;">
            
            <!-- HEADER -->
            <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">
                    Lịch Sử Điểm Tín Dụng
                </h3>
                <button type="button" onclick="closeUserReputationModal()" style="background: none; border: none; font-size: 1.4rem; color: #64748b; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            
            <!-- BODY -->
            <div style="padding: 24px;">
                <!-- TOP USER INFO -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($user['ho_ten']); ?></h4>
                        <span style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($user['email'] ?: $user['sdt']); ?></span>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div style="text-align: right;">
                            <span style="display: block; font-size: 0.72rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Điểm hiện tại</span>
                            <strong style="font-size: 1.25rem; font-weight: 800; color: <?php echo ($score >= 80) ? '#059669' : (($score >= 60) ? '#0284c7' : '#dc2626'); ?>;">
                                <?php echo $score; ?>/100
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- LOG TABLE -->
                <?php if (isset($reputation_history) && $reputation_history->num_rows > 0): ?>
                    <?php $total_user_logs = $reputation_history->num_rows; ?>
                    <div style="border: 1px solid #e2e8f0; border-radius: 10px; background: #ffffff; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.86rem; text-align: center;">
                            <thead>
                                <tr style="background: #f1f5f9; color: #475569; font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <th style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: center; width: 140px; white-space: nowrap;">THỜI GIAN</th>
                                    <th style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: center; width: 110px; white-space: nowrap;">BIẾN ĐỘNG</th>
                                    <th style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: center; width: 130px; white-space: nowrap;">ĐIỂM SAU CÙNG</th>
                                    <th style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0; text-align: center;">NỘI DUNG CHI TIẾT</th>
                                </tr>
                            </thead>
                            <tbody id="userRepLogTableBody">
                                <?php 
                                $reputation_history->data_seek(0);
                                $u_idx = 0;
                                while($rh = $reputation_history->fetch_assoc()): 
                                    $u_idx++;
                                    $pts = (int)$rh['so_diem'];
                                    if ($pts > 0) {
                                        $badge = "<span style='display: inline-block; white-space: nowrap; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.82rem;'>+$pts đ</span>";
                                    } elseif ($pts < 0) {
                                        $badge = "<span style='display: inline-block; white-space: nowrap; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.82rem;'>$pts đ</span>";
                                    } else {
                                        $badge = "<span style='display: inline-block; white-space: nowrap; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.82rem;'>0 đ</span>";
                                    }
                                ?>
                                <tr class="user-rep-row" data-index="<?php echo $u_idx; ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;">
                                    <td style="padding: 12px 14px; color: #64748b; font-size: 0.82rem; white-space: nowrap; text-align: center; vertical-align: middle;">
                                       <?php echo date('d/m/Y H:i', strtotime($rh['ngay_tao'])); ?>
                                    </td>
                                    <td style="padding: 12px 14px; text-align: center; vertical-align: middle; white-space: nowrap;">
                                        <?php echo $badge; ?>
                                    </td>
                                    <td style="padding: 12px 14px; font-weight: 800; color: #0f172a; text-align: center; vertical-align: middle; white-space: nowrap;">
                                        <?php echo $rh['diem_sau_cung']; ?>/100
                                    </td>
                                    <td style="padding: 12px 16px; color: #334155; font-weight: 600; text-align: center; vertical-align: middle;">
                                        <?php echo htmlspecialchars($rh['ghi_chu']); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION CONTROLS -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 6px; flex-wrap: wrap; gap: 10px;">
                        <span id="userRepPaginationInfo" style="font-size: 0.82rem; color: #64748b; font-weight: 600;">
                            Hiển thị 1 - 5 / <?php echo $total_user_logs; ?> bản ghi
                        </span>
                        <div id="userRepPaginationBtns" style="display: flex; gap: 6px; align-items: center;"></div>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #94a3b8; padding: 24px;">Chưa có dữ liệu biến động điểm nào.</p>
                <?php endif; ?>
            </div>

            <!-- FOOTER -->
            <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc;">
                <button type="button" onclick="closeUserReputationModal()" style="padding: 8px 20px; font-size: 0.88rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-weight: 600; cursor: pointer;">Đóng</button>
            </div>
        </div>
    </div>

</div>

<script src="assets/js/profile.js?v=1.0.1"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
