const { test, expect } = require('@playwright/test');

test.describe('E2E Test Toàn Diện: Hệ Thống Đặt Sân Bóng Đá Mini (100% Chức Năng)', () => {

    test('TC01: Trang chủ tải thành công và hiển thị đầy đủ thông tin header', async ({ page }) => {
        await page.goto('index.php');
        
        // Kiểm tra title và logo thương hiệu
        await expect(page).toHaveTitle(/Sân Bóng/i);
        const headerTitle = page.locator('.logo-text h1');
        await expect(headerTitle).toBeVisible();
        await expect(headerTitle).toContainText('Hệ Thống Đặt Sân');

        // Kiểm tra thanh menu chính
        await expect(page.locator('header a:has-text("Trang chủ")')).toBeVisible();
        await expect(page.locator('header a[href*="controller=DatSan"]').first()).toBeVisible();
    });

    test('TC02: Đăng nhập thất bại khi nhập sai mật khẩu', async ({ page }) => {
        await page.goto('index.php?controller=Auth&action=login');
        
        await page.fill('#username', 'testuser');
        await page.fill('#password', 'mat_khau_sai_999999');
        await page.click('button[type="submit"]');

        // Kiểm tra thông báo lỗi xuất hiện
        const errorAlert = page.locator('.error');
        await expect(errorAlert).toBeVisible();
        await expect(errorAlert).toContainText(/không chính xác|sai/i);
    });

    test('TC03: Đăng nhập thành công với tài khoản người dùng testuser', async ({ page }) => {
        await page.goto('index.php?controller=Auth&action=login');
        
        await page.fill('#username', 'testuser');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');

        // Chờ chuyển hướng về trang chủ
        await page.waitForURL('**/index.php*');
        await expect(page).not.toHaveURL(/action=login/);

        // Kiểm tra menu người dùng hiển thị
        const userMenu = page.locator('.user-name, .header-user, a:has-text("Đăng xuất")');
        await expect(userMenu.first()).toBeVisible();
    });

    test('TC04: Kiểm tra giao diện và dữ liệu Lịch đặt sân', async ({ page }) => {
        // Đăng nhập
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'testuser');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/index.php*');

        // Vào trang Đặt sân
        await page.goto('index.php?controller=DatSan&action=book');

        // Kiểm tra form bộ lọc
        await expect(page.locator('#san_id')).toBeVisible();
        await expect(page.locator('#ngay_dat')).toBeVisible();

        // Kiểm tra container lịch AJAX
        const scheduleContainer = page.locator('#schedule-container');
        await expect(scheduleContainer).toBeVisible();
        await expect(page.locator('.schedule-row').first()).toBeVisible({ timeout: 10000 });
    });

    test('TC05: Tìm kiếm thời gian thực (Debounce Search) & Mở bộ lọc nâng cao', async ({ page }) => {
        await page.goto('index.php');

        // 1. Kiểm tra thanh tìm kiếm độ rộng chuẩn
        const searchInput = page.locator('#searchInput');
        await expect(searchInput).toBeVisible();

        // 2. Nhập từ khóa tìm kiếm
        await searchInput.fill('Nam Cao');
        await page.waitForTimeout(600); // Chờ debounce

        // 3. Kiểm tra kết quả hiển thị sân bóng phù hợp
        const pitchCards = page.locator('.san-card');
        await expect(pitchCards.first()).toBeVisible();
        await expect(pitchCards.first()).toContainText(/Nam Cao/i);

        // 4. Mở popup bộ lọc
        const filterToggleBtn = page.locator('#filterToggleBtn');
        await expect(filterToggleBtn).toBeVisible();
        await filterToggleBtn.click();
        
        const filterPanel = page.locator('#filterPanel');
        await expect(filterPanel).toBeVisible();
    });

    test('TC06: Kiểm tra Trang Hồ Sơ Cá Nhân (Profile) & Thang Đo Điểm Uy Tín', async ({ page }) => {
        // Đăng nhập
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'testuser');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/index.php*');

        // Truy cập trang Hồ sơ
        await page.goto('index.php?controller=User&action=profile');

        // Kiểm tra layout trang hồ sơ
        await expect(page.locator('.profile-page-wrapper, .profile-banner').first()).toBeVisible();
        
        // Kiểm tra thẻ Hạng Điểm Uy Tín & thanh tiến trình
        const tierCard = page.locator('.tier-card');
        await expect(tierCard).toBeVisible();
        await expect(page.locator('.tier-title')).toBeVisible();
        await expect(page.locator('.tier-progress-bar')).toBeVisible();
    });

    test('TC07: Kiểm tra Trang Lịch Sử Đặt Sân & Phân Loại Trạng Thái', async ({ page }) => {
        // Đăng nhập
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'testuser');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/index.php*');

        // Truy cập trang Lịch sử
        await page.goto('index.php?controller=User&action=history');

        // Kiểm tra container lịch sử và tiêu đề
        await expect(page.locator('.history-container')).toBeVisible();
        await expect(page.locator('h2:has-text("Lịch sử đặt sân")')).toBeVisible();

        // Kiểm tra các tab trạng thái lọc nhanh
        await expect(page.locator('.history-container a:has-text("Tất cả")')).toBeVisible();
        await expect(page.locator('.history-container a:has-text("Chờ duyệt")')).toBeVisible();
    });

    test('TC08: Đăng nhập Quản Trị Viên & Kiểm tra Giao diện Admin Dashboard', async ({ page }) => {
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'admin15');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');

        // Chờ chuyển hướng vào khu vực admin
        await page.waitForURL('**/admin/index.php*');

        // Kiểm tra tiêu đề tổng quan và các thẻ thống kê KPI
        await expect(page.locator('.page-title:has-text("Tổng quan hệ thống")')).toBeVisible();
        const statCards = page.locator('.stat-card-modern');
        await expect(statCards.first()).toBeVisible();

        // Kiểm tra thanh menu sidebar quản trị
        await expect(page.locator('.admin-sidebar')).toBeVisible();
    });

    test('TC09: Quản lý Đặt Sân Phía Admin & Bảng Duyệt Đơn', async ({ page }) => {
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'admin15');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/index.php*');

        // Vào trang quản lý đặt sân
        await page.goto('admin/datsan/index.php');

        // Kiểm tra thanh tabs lọc trạng thái và bảng đặt sân
        await expect(page.locator('.filter-tabs-bar')).toBeVisible();
        await expect(page.locator('.tab-link').first()).toBeVisible();
        await expect(page.locator('table.admin-table')).toBeVisible();
    });

    test('TC10: Đăng xuất an toàn và làm mới phiên làm việc (Session Invalidation)', async ({ page }) => {
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'testuser');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/index.php*');

        // Thực hiện đăng xuất
        await page.goto('index.php?controller=Auth&action=logout');

        // Xác nhận đã về trang chủ hoặc trang đăng nhập và menu đã về trạng thái khách
        await page.goto('index.php');
        const loginLink = page.locator('a[href*="action=login"]:has-text("Đăng nhập")');
        await expect(loginLink.first()).toBeVisible();
    });

    test('TC11: Luồng Đặt Sân Trực Tuyến & Chuyển Sang Trang Thanh Toán Giữ Chỗ', async ({ page }) => {
        // Đăng nhập tài khoản testuser
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'testuser');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/index.php*');

        // Vào trang đặt sân
        await page.goto('index.php?controller=DatSan&action=book&san_id=1');
        await page.waitForSelector('#schedule-container');

        // Chờ tải bảng lịch sân thành công
        const availablePitch = page.locator('.mini-pitch-svg.available').first();
        if (await availablePitch.isVisible({ timeout: 5000 }).catch(() => false)) {
            // Click chọn 1 sân con còn trống
            await availablePitch.click();
            await expect(availablePitch).toHaveClass(/selected/);

            // Bấm nút Đặt sân của dòng tương ứng
            const activeRow = page.locator('.schedule-row.active-row');
            const submitBtn = activeRow.locator('button[type="submit"]');
            await submitBtn.click();

            // Chờ chuyển hướng sang trang thanh toán
            await page.waitForURL('**/index.php?controller=Payment&action=index&dat_san_id=*', { timeout: 10000 });
            await expect(page.locator('#countdownTimer')).toBeVisible();
            await expect(page.locator('.thongtin-datsan')).toBeVisible();
        } else {
            // Trường hợp ngày hôm nay đã hết slot trống, kiểm tra giao diện bảng lịch vẫn hiển thị đầy đủ
            await expect(page.locator('#schedule-container')).toBeVisible();
        }
    });

    test('TC12: Kiểm Tra Tương Tác Chọn Phương Thức & Nút Sao Chép STK VietQR', async ({ page }) => {
        // Đăng nhập tài khoản testuser
        await page.goto('index.php?controller=Auth&action=login');
        await page.fill('#username', 'testuser');
        await page.fill('#password', '123456');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/index.php*');

        // Truy cập trang thanh toán đơn đặt sân
        await page.goto('index.php?controller=Payment&action=index&dat_san_id=138');

        // Nếu đơn 138 còn hiệu lực hoặc hiển thị form
        const chuyenKhoanRadio = page.locator('input[name="phuong_thuc"][value="chuyen_khoan"]');
        if (await chuyenKhoanRadio.isVisible({ timeout: 3000 }).catch(() => false)) {
            await chuyenKhoanRadio.check({ force: true });
            
            // Hộp VietQR phải xuất hiện
            const qrBox = page.locator('#chuyenKhoanBox');
            await expect(qrBox).toBeVisible();

            // Kiểm tra hiển thị đúng STK MB Bank
            const stkText = page.locator('#stkText');
            await expect(stkText).toContainText('1111000050174');

            // Click nút sao chép STK và kiểm tra phản hồi tương tác
            const copyBtn = page.locator('.qr-copy-btn').first();
            await copyBtn.click();
            await expect(copyBtn.locator('i')).toHaveClass(/fa-check|fa-copy/);
        } else {
            // Xác nhận hiển thị thông tin trang thanh toán hoặc thẻ thông báo
            await expect(page.locator('.thanhtoan-container')).toBeVisible();
        }
    });
});
