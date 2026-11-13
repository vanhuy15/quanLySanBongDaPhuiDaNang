<?php
// Đảm bảo session đã start
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

$vai_tro = isset($_SESSION['vai_tro']) ? $_SESSION['vai_tro'] : '';
$ctrl = $_GET['controller'] ?? 'Home';
$action = $_GET['action'] ?? 'index';

// Helper for active link
function isActive($expectedCtrl, $currentCtrl) {
    return ($expectedCtrl === $currentCtrl) ? 'active' : '';
}
?>

        <nav class="main-menu">
            <ul>
                <li><a href="index.php" class="<?php echo isActive('Home', $ctrl); ?>"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><a href="index.php?controller=DatSan&action=book" class="<?php echo isActive('DatSan', $ctrl); ?>"><i class="fas fa-futbol"></i> Đặt sân</a></li>
                <li><a href="index.php?controller=User&action=history" class="<?php echo ($ctrl == 'User' && $action == 'history') ? 'active' : ''; ?>"><i class="fas fa-history"></i> Lịch sử</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <?php if(isset($_SESSION['nguoi_dung_id']) && $vai_tro === 'khach_hang'): ?>
                <div class="nav-notification-item">
                    <button type="button" class="btn-notification-bell" id="notificationBellBtn" onclick="toggleNotificationDropdown(event)" title="Thông báo">
                        <i class="far fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>
                    <div class="notification-dropdown-menu" id="notificationDropdown">
                        <div class="notification-header">
                            <div class="notif-title"><i class="fas fa-bell" style="color: var(--primary-color);"></i> Thông báo</div>
                            <button type="button" class="btn-mark-all-read" onclick="markAllNotificationsRead(event)">Đã đọc tất cả</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-empty"><i class="fas fa-spinner fa-spin"></i> Đang tải thông báo...</div>
                        </div>
                        <div class="notification-footer">
                            <a href="index.php?controller=User&action=history">Xem tất cả lịch sử đặt sân <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <?php 
                    $menuAvatar = !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : 'images/avatar/default_avatar.svg';
                ?>
                <a href="index.php?controller=User&action=profile" class="user-profile-chip">
                    <img src="<?php echo htmlspecialchars($menuAvatar); ?>" alt="Avatar" class="menu-user-avatar" id="menuUserAvatar" onerror="this.src='images/avatar/default_avatar.svg'">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['ho_ten'] ?? 'Cá nhân'); ?></span>
                </a>
                
                <a href="index.php?controller=Auth&action=logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            <?php else: ?>
                <a href="index.php?controller=Auth&action=login" class="login-btn"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                <a href="index.php?controller=Auth&action=register" class="register-btn"><i class="fas fa-user-plus"></i> Đăng ký</a>
            <?php endif; ?>
        </div>
    </div> <!-- .header-container -->
</header>