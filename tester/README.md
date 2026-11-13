# 🧪 Bộ Tài Liệu & Kịch Bản Kiểm Thử Phần Mềm Toàn Diện (Full Software Testing Suite)

> Hệ thống kiểm thử tự động, tải đồng thời, API và an ninh mạng bao phủ **100% chức năng & thuật toán cốt lõi** cho dự án **Quản Lý Đặt Sân Bóng Đá Phủi Đà Nẵng** (Mô hình Pure MVC Thuần - PHP 8.2 + MySQL + Vanilla JS + Modern Modular CSS).

---

## 🏗️ Tổng Quan 5 Công Cụ Kiểm Thử Trong Thư Mục `tester/`

```text
tester/
├── README.md                                  # Hướng dẫn tổng hợp & quy trình thực thi
├── 01_unit_test/                              # 🟢 TOOL 1: PHPUnit (47 Tests - 129 Assertions - 100% Pass)
│   ├── BoundaryAndSecurityTest.php            # Kiểm thử giá trị biên (BVA 12h/24h/đêm), mã hóa BCRYPT, khóa tài khoản
│   ├── CancellationPenaltyCalculationTest.php # Thuật toán phạt điểm uy tín theo mốc giờ hủy & hoàn tiền
│   ├── CompoundPitchLockTest.php              # Thuật toán ghép sân 5 và 7 (Khóa chéo đa năng san_con_khoa)
│   ├── CsrfTest.php                           # Test sinh mã & xác thực Anti-CSRF Token 64-hex
│   ├── HoldTimerAndBookingStatusTest.php      # Thuật toán giữ chỗ 10 phút & tự động nhả slot
│   ├── InputValidationAndSanitizationTest.php # Validation SĐT VN, Email, Ngày giờ, XSS Sanitization
│   ├── PricingCalculationTest.php             # Quy tắc tính giá sân 5 và sân 7 ghép
│   ├── ReputationPolicyTest.php               # Quy tắc Điểm uy tín & điều kiện khóa tiền mặt/đặt sân
│   ├── VietQRFormatTest.php                   # Chuẩn hóa URL VietQR Napas247 MB Bank
│   └── phpunit.phar                           # File thực thi độc lập PHPUnit 10.5
├── 02_api_test/                               # 🟠 TOOL 2: Postman & Newman (25 Requests - 34 Assertions - 100% Pass)
│   ├── QuanLySanBong_API.postman_collection.json # 6 Phân hệ: Auth, Discovery, Booking, Checkout, User, Admin
│   ├── QuanLySanBong.postman_environment.json    # Môi trường cấu hình Local host, user, admin credentials
│   └── README.md                              # Hướng dẫn chạy CLI qua Newman hoặc Postman Desktop
├── 03_e2e_ui_test/                            # 🔵 TOOL 3: Playwright E2E UI (12 Flow Scenarios - 100% Pass)
│   ├── booking.spec.js                        # Kịch bản E2E kiểm tra toàn bộ luồng Client & Admin, đặt sân & copy STK
│   └── playwright-report/                     # Báo cáo HTML trực quan, screenshots và traces
├── 04_load_concurrency_test/                  # 🟣 TOOL 4: Apache JMeter (3 Load & Concurrency Plans)
│   ├── RaceCondition_Booking_Test.jmx         # 50 users tranh đặt cùng 1 slot (SELECT ... FOR UPDATE)
│   ├── 02_CompoundPitch_Conflict_Test.jmx     # Tranh chấp đồng thời Sân 7 ghép vs Sân con lẻ A
│   ├── 03_HighConcurrency_ReadSearch_Test.jmx # Tải đọc cao độ 200 CCU tìm kiếm debounce & bảng giá
│   ├── run_jmeter_test.bat                    # Script chạy 1 chạm tự động tạo báo cáo HTML Dashboard
│   ├── test_users_data.csv                    # Dữ liệu tài khoản tham số hóa (Parameterized Data)
│   └── README.md                              # Hướng dẫn chạy JMeter Non-GUI mode & phân tích biểu đồ
└── 05_security_test/                          # 🔴 TOOL 5: OWASP ZAP & Automated Auditor (9 Chốt Chặn - 100% Pass)
    ├── security_audit_runner.php              # Script PHP kiểm thử tự động 9/9 lỗ hổng an ninh mạng (OWASP Top 10)
    ├── quanlysanbong.context                  # File cấu hình Context nhập sẵn cho phần mềm OWASP ZAP GUI
    └── README.md                              # Báo cáo phân tích DAST, CVE và hướng dẫn quét bảo mật
```

---

## ⚡ Bảng Lệnh Terminal Chạy Nhanh Toàn Bộ Test Suite (NPM Scripts)

Toàn bộ các công cụ đã được tích hợp thành lệnh npm một chạm trong file `package.json`:

| Lệnh Terminal | Công cụ kiểm thử | Số ca kiểm thử | Kết quả bảo đảm |
| :--- | :--- | :---: | :---: |
| `npm run test:unit` | **PHPUnit 10.5** (Unit Test / Hộp trắng) | 47 tests / 129 assertions | ✅ **100% Passed (0.01s)** |
| `npm run test:api` | **Newman CLI** (API & Tích hợp) | 25 requests / 34 assertions | ✅ **100% Passed (2.9s)** |
| `npm run test:e2e` | **Playwright** (E2E UI Headless) | 12 kịch bản luồng | ✅ **100% Passed (34.3s)** |
| `npm run test:e2e:headed` | **Playwright** (Giao diện trực quan Chromium) | 12 kịch bản luồng | ✅ **100% Passed** |
| `npm run test:report` | **Playwright Report** (Xem báo cáo HTML & Trace) | - | 📊 **Mở trình duyệt báo cáo** |
| `npm run test:security` | **Automated Auditor** (9 Chốt bảo mật OWASP) | 9 chốt an toàn | ✅ **9/9 Passed (100% An toàn)** |
| `npm run test:all` | **Chạy liên hoàn cả 4 bộ test tự động** | Toàn bộ hệ thống | ✅ **Bảo đảm 100% Pass** |

---

## 📋 Hướng Dẫn Chi Tiết Từng Công Cụ

### 1️⃣ Tool 1: Unit Test (Kiểm thử Hộp Trắng - PHPUnit)
Bao phủ toàn bộ các thuật toán cốt lõi, công thức giá, phân tích giá trị biên (BVA) và bảo mật mật khẩu:
```bash
npm run test:unit
```
*Các nội dung đã kiểm thử:*
1. Khóa chéo ghép sân 5 và 7 (`san_con_khoa = 'A,B'`).
2. Bộ đếm giữ chỗ 10 phút & tự động hủy đơn quá hạn.
3. Bảng phạt điểm uy tín hủy đơn ($>24$h, $12-24$h, $<12$h, bùng sân).
4. Phân tích giá trị biên BVA tại mốc chính xác $24.0$h và $12.0$h.
5. Ca đá tối muộn xuyên đêm ($23:00 - 00:30$) và ràng buộc thời lượng trận đấu.
6. Kẹp biên điểm uy tín $[0, 100]$.
7. Mã hóa mật khẩu BCRYPT (`$2y$`, 60 ký tự, `password_verify`).
8. Chặn đăng nhập khi tài khoản bị admin khóa thủ công (`khoa_tai_khoan = 1`).
9. Chuẩn hóa URL mã VietQR Napas 247 MB Bank.
10. Lọc dữ liệu đầu vào, Anti-CSRF Token 64-hex, XSS Sanitization, Regex SĐT/Email.

---

### 2️⃣ Tool 2: API & Integration Test (Postman / Newman)
Kiểm thử 25 endpoints xuyên suốt 6 phân hệ Client & Admin có kèm trích xuất biến động (Chained State Flow) và kiểm thử âm tính (Negative Testing):
```bash
npm run test:api
```
*Các nội dung đã kiểm thử:*
1. Khởi tạo phiên, trích xuất CSRF Token từ thẻ meta.
2. Chặn truy cập trang đặt sân khi chưa đăng nhập (Redirect 302).
3. Đăng nhập tài khoản Khách hàng (`testuser`) và xác lập cookie `PHPSESSID`.
4. Đăng nhập thất bại khi sai mật khẩu và hiển thị cảnh báo.
5. Chặn request POST khi thiếu hoặc sai CSRF Token (Trả về mã HTTP 403 Forbidden).
6. Tải trang chủ danh sách sân bóng (`san-bong-container`).
7. Tìm kiếm thời gian thực Debounce theo từ khóa ("Nam Cao").
8. Lọc sân theo quy mô (Sân 5 / Sân 7) và khoảng giá.
9. Lấy ma trận lịch sân bóng qua AJAX (`getSchedule`).
10. Kiểm tra slot sân 7 ghép (`mode=san_7`).
11. Gửi đơn đặt sân tạo booking mới và trích xuất động `latest_booking_id`.
12. Xem trang thanh toán kèm bộ đếm giữ chỗ 10 phút với `latest_booking_id`.
13. Xác nhận chọn phương thức thanh toán VietQR.
14. Kiểm thử âm tính: Truyền `dat_san_id = -1` xử lý an toàn không gây crash 500.
15. Xem lịch sử đặt sân của người dùng.
16. Xem hồ sơ cá nhân và bảng Điểm Uy Tín.
17. Lấy danh sách thông báo qua AJAX (`getLatest`).
18. Gửi đánh giá sao sân bóng và cộng điểm uy tín (`Review/submit`).
19. Đăng nhập Quản trị viên (`admin15`).
20. KPI Dashboard và biểu đồ doanh thu Admin.
21. Quản lý đặt sân: Lọc đơn theo trạng thái (Chờ xác nhận, Đã duyệt).
22. Danh sách sân bóng và cấu hình ghép sân 5 và 7.
23. Quản lý khung giờ và bảng giá sân.
24. Quản lý khách hàng và can thiệp điểm uy tín (`admin/user/index.php`).

---

### 3️⃣ Tool 3: E2E UI Test (Playwright)
Mô phỏng 100% thao tác thực tế của người dùng trên trình duyệt Chromium:
```bash
npm run test:e2e
```
*12 Kịch bản kiểm thử luồng giao diện:*
- `TC01`: Trang chủ tải thành công, hiển thị header, menu điều hướng và danh sách sân bóng.
- `TC02`: Đăng nhập thất bại khi nhập sai mật khẩu (Báo lỗi đỏ).
- `TC03`: Đăng nhập thành công với tài khoản khách hàng `testuser`.
- `TC04`: Xem ma trận lịch đặt sân bóng, chọn ngày và kiểm tra ô giờ.
- `TC05`: Tìm kiếm thời gian thực (Debounce Search) & mở drawer bộ lọc giá/loại sân.
- `TC06`: Kiểm tra trang Hồ sơ cá nhân (Profile) & Thang đo Điểm Uy Tín.
- `TC07`: Kiểm tra trang Lịch sử đặt sân & Phân loại trạng thái (Tất cả / Chờ duyệt / Đã hủy).
- `TC08`: Đăng nhập Quản trị viên & kiểm tra KPI Dashboard, sidebar admin.
- `TC09`: Quản lý đặt sân phía Admin & duyệt đơn trực quan.
- `TC10`: Đăng xuất an toàn và làm mới phiên làm việc (Session Invalidation).
- `TC11`: **Luồng Đặt Sân Trực Tuyến & Chuyển Sang Trang Thanh Toán Giữ Chỗ (Click sơ đồ 2D -> Chọn sân con A -> Đặt ngay -> Mở trang thanh toán giữ chỗ 10 phút)**.
- `TC12`: **Kiểm tra tương tác chọn phương thức thanh toán QR chuyển khoản & Phản hồi nút sao chép STK VietQR (Icon đổi thành checkmark)**.

---

### 4️⃣ Tool 4: Concurrency & Load Test (Apache JMeter)
Kiểm tra khả năng chịu tải và triệt tiêu Race Condition:
- Kịch bản 1 (`RaceCondition_Booking_Test.jmx`): 50 users tranh đặt cùng 1 slot sân con trong cùng 1 tích tắc. Kết quả: Duy nhất 1 đơn thành công, 49 đơn nhận thông báo xung đột.
- Kịch bản 2 (`02_CompoundPitch_Conflict_Test.jmx`): Tranh chấp giữa Sân 7 ghép và Sân 5 thành phần lẻ A. Kết quả: Khi sân 7 đặt thành công, toàn bộ sân 5 thành phần tự động bị khóa chéo.
- Kịch bản 3 (`03_HighConcurrency_ReadSearch_Test.jmx`): 200 CCU tải đọc tìm kiếm debounce và xem lịch AJAX liên tục. Kết quả: 0% lỗi, phản hồi trung bình $< 200$ms.

*Cách chạy:* Chạy file `run_jmeter_test.bat` bằng cách nhấp đúp chuột, hoặc chạy lệnh:
```bash
jmeter -n -t tester/04_load_concurrency_test/RaceCondition_Booking_Test.jmx -l tester/04_load_concurrency_test/results_race.jtl
```

---

### 5️⃣ Tool 5: Security Testing (OWASP ZAP & Automated Auditor)
Kiểm tra an toàn thông tin chuyên sâu theo danh mục OWASP Top 10:
```bash
npm run test:security
```
*9 Chốt chặn an ninh mạng được kiểm thử tự động:*
1. **[SEC-01] SQL Injection**: Prepared Statements tham số hóa 100%, triệt tiêu SQLi trên mọi trường form và query string.
2. **[SEC-02] Cross-Site Scripting (XSS)**: Hàm `htmlspecialchars()` và bộ lọc Router loại bỏ payload script nguy hiểm.
3. **[SEC-03] Anti-CSRF Token**: Toàn bộ form POST bắt buộc token 64-hex hợp lệ; từ chối với HTTP 403 Forbidden nếu thiếu.
4. **[SEC-04] Broken Authentication & RBAC**: Chặn khách vãng lai và tài khoản người dùng thường truy cập trái phép phân hệ Quản trị (`admin/`).
5. **[SEC-05] Security Response Headers**: Thiết lập đầy đủ `X-Frame-Options: SAMEORIGIN` (Chống Clickjacking), `X-Content-Type-Options: nosniff`, `Referrer-Policy` và `Content-Security-Policy`.
6. **[SEC-06] Session Security**: Cờ `HttpOnly: true` và `SameSite: Lax` bảo vệ cookie phiên `PHPSESSID` khỏi tấn công đánh cắp phiên qua JavaScript.
7. **[SEC-07] Information Disclosure**: Ẩn header `X-Powered-By` che giấu phiên bản PHP của máy chủ.
8. **[SEC-08] Insecure Direct Object Reference (IDOR)**: Kiểm tra quyền sở hữu `WHERE dat_san_id = ? AND nguoi_dung_id = ?`, chặn truy cập trái phép hóa đơn/đơn đặt sân của người dùng khác.
9. **[SEC-09] Malicious File Upload Prevention**: Chặn tải lên file webshell PHP giả danh ảnh đại diện (`webshell.php.png`), kiểm tra nghiêm ngặt phần mở rộng, MIME type và hàm `getimagesize`.

*File cấu hình OWASP ZAP GUI:* Nhập file `tester/05_security_test/quanlysanbong.context` vào OWASP ZAP để tự động nhận diện URL đăng nhập và loại trừ các endpoint đăng xuất khi quét tự động.
