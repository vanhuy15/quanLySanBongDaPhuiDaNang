document.addEventListener('DOMContentLoaded', function() {
    console.log('Hệ thống đặt sân bóng đá trực tuyến đã sẵn sàng!');

    animateCards();
    setupPaymentMethod();
    setupSmoothScroll();
    setupDateValidation();
    setupLogoutConfirm();
    setupButtonLoadingEffects();
    initNotificationSystem();

    // Lắng nghe sự kiện click trên bản đồ 2D
    setupMiniPitchMap();

    if (document.querySelector('.lichsu-table')) setupLichSuPage();
});

function animateCards() {
    const cards = document.querySelectorAll('.san-card, .san-item');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 60);
    });
}

function setupPaymentMethod() {
    const radios = document.querySelectorAll('input[name="phuong_thuc"]');
    if (radios.length === 0) return;

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            const chuyenKhoanBox = document.getElementById('chuyenKhoanBox');
            if (!chuyenKhoanBox) return;

            if (this.value === 'chuyen_khoan') {
                chuyenKhoanBox.style.display = 'block';
                chuyenKhoanBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                chuyenKhoanBox.style.display = 'none';
            }
        });
    });
}

function setupSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

function setupDateValidation() {
    const dateInput = document.querySelector('input[name="ngay_dat"]');
    if (!dateInput) return;

    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
}

function setupLogoutConfirm() {
    const logoutLinks = document.querySelectorAll('a.logout, a[href*="action=logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc muốn đăng xuất khỏi tài khoản?')) {
                e.preventDefault();
            }
        });
    });
}

function setupButtonLoadingEffects() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.dataset.originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                submitBtn.style.opacity = '0.8';
                setTimeout(() => {
                    if (submitBtn.dataset.originalText) {
                        submitBtn.innerHTML = submitBtn.dataset.originalText;
                        submitBtn.style.opacity = '1';
                    }
                }, 5000);
            }
        });
    });
}

// Global Toast Notification Helper
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = `
            position: fixed;
            top: 25px;
            right: 25px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 99999;
        `;
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    toast.style.cssText = `
        background: ${isSuccess ? 'linear-gradient(135deg, #0d9488, #0f766e)' : 'linear-gradient(135deg, #ef4444, #dc2626)'};
        color: white;
        padding: 14px 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        opacity: 0;
        transform: translateY(-20px);
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    `;
    toast.innerHTML = `<i class="fas ${isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="font-size: 1.1rem;"></i> <span>${message}</span>`;
    
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

function setupLichSuPage() {
    const statusCells = document.querySelectorAll('.lichsu-table td:nth-child(5)');
    statusCells.forEach(cell => {
        const status = cell.textContent.trim();
        let badgeClass = 'status-badge ';
        
        if (status === 'Chờ xác nhận') {
            badgeClass += 'status-cho-xac-nhan';
        } else if (status === 'Đã xác nhận') {
            badgeClass += 'status-da-xac-nhan';
        } else if (status === 'Đã hủy') {
            badgeClass += 'status-huy';
        }
        if (cell.children.length === 0) {
            cell.innerHTML = `<span class="${badgeClass}">${status}</span>`;
        }
    });
}

function setupMiniPitchMap() {
    const datsanTable = document.querySelector('.lichsu-table'); 
    if (!datsanTable) return;

    datsanTable.addEventListener('click', function(e) {
        const clickedPitch = e.target.closest('.mini-pitch-svg.available');
        
        if (clickedPitch) {
            const pitchName = clickedPitch.dataset.pitchName;
            const row = clickedPitch.closest('tr');
            if (row) {
                const allPitchesInRow = row.querySelectorAll('.mini-pitch-svg');
                allPitchesInRow.forEach(pitch => {
                    pitch.classList.remove('pitch-selected');
                });

                clickedPitch.classList.add('pitch-selected');

                const hiddenInput = row.querySelector('input.hidden-pitch-name');
                if (hiddenInput) {
                    hiddenInput.value = pitchName;
                }

                const bookingButton = row.querySelector('button.btn-thanhtoan');
                if (bookingButton) {
                    bookingButton.disabled = false;
                }
            }
        }
    });
}

// ==========================================================
// === NOTIFICATION SYSTEM JAVASCRIPT ===
// ==========================================================
function initNotificationSystem() {
    const bellBtn = document.getElementById('notificationBellBtn');
    if (!bellBtn) return;

    // Load initial notification count
    fetchNotificationData();

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown && !e.target.closest('.nav-notification-item')) {
            dropdown.classList.remove('show');
            bellBtn.classList.remove('active');
        }
    });
}

function toggleNotificationDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    const bellBtn = document.getElementById('notificationBellBtn');
    if (!dropdown || !bellBtn) return;

    const isShowing = dropdown.classList.contains('show');
    if (isShowing) {
        dropdown.classList.remove('show');
        bellBtn.classList.remove('active');
    } else {
        dropdown.classList.add('show');
        bellBtn.classList.add('active');
        fetchNotificationData();
    }
}

function fetchNotificationData() {
    const badge = document.getElementById('notificationBadge');
    const listContainer = document.getElementById('notificationList');

    fetch('index.php?controller=Notification&action=getLatest')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update badge
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }

                // Render list
                if (listContainer) {
                    if (data.notifications && data.notifications.length > 0) {
                        let html = '';
                        data.notifications.forEach(item => {
                            let iconClass = 'info';
                            let iconTag = '<i class="fas fa-bell"></i>';
                            
                            if (item.loai === 'xac_nhan') {
                                iconClass = 'xac_nhan';
                                iconTag = '<i class="fas fa-check-circle"></i>';
                            } else if (item.loai === 'huy_san') {
                                iconClass = 'huy_san';
                                iconTag = '<i class="fas fa-times-circle"></i>';
                            } else if (item.loai === 'dat_san') {
                                iconClass = 'dat_san';
                                iconTag = '<i class="fas fa-futbol"></i>';
                            } else if (item.loai === 'thanh_toan') {
                                iconClass = 'thanh_toan';
                                iconTag = '<i class="fas fa-money-bill-wave"></i>';
                            }

                            const unreadClass = item.da_doc == 0 ? 'unread' : '';
                            const link = item.lien_ket || 'index.php?controller=User&action=history';

                            html += `
                                <div class="notification-item ${unreadClass}" onclick="handleNotificationClick(${item.thong_bao_id}, '${link}')">
                                    <div class="notification-icon ${iconClass}">
                                        ${iconTag}
                                    </div>
                                    <div class="notification-content">
                                        <div class="notif-item-title">${escapeHtml(item.tieu_de)}</div>
                                        <div class="notif-item-text">${escapeHtml(item.noi_dung)}</div>
                                        <div class="notif-item-time">${escapeHtml(item.time_ago)}</div>
                                    </div>
                                </div>
                            `;
                        });
                        listContainer.innerHTML = html;
                    } else {
                        listContainer.innerHTML = '<div class="notification-empty"><i class="fas fa-inbox" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>Bạn chưa có thông báo nào.</div>';
                    }
                }
            }
        })
        .catch(err => console.error(err));
}

function handleNotificationClick(id, link) {
    const formData = new FormData();
    formData.append('id', id);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) formData.append('csrf_token', csrfToken);

    fetch('index.php?controller=Notification&action=markRead', {
        method: 'POST',
        body: formData,
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}
    })
    .then(() => {
        window.location.href = link;
    })
    .catch(() => {
        window.location.href = link;
    });
}

function markAllNotificationsRead(e) {
    e.stopPropagation();
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    if (csrfToken) formData.append('csrf_token', csrfToken);

    fetch('index.php?controller=Notification&action=markAllRead', {
        method: 'POST',
        body: formData,
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('notificationBadge');
            if (badge) badge.style.display = 'none';
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            showToast('Đã đánh dấu tất cả thông báo là đã đọc', 'success');
        }
    })
    .catch(err => console.error(err));
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}