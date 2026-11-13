# 🧪 Bộ Tài Liệu & Kịch Bản Kiểm Thử Phần Mềm Toàn Diện (Full Software Testing Suite)

> Hệ thống kiểm thử tự động, tải đồng thời, API và an ninh mạng bao phủ **100% chức năng & thuật toán cốt lõi** cho dự án **Quản Lý Đặt Sân Bóng Đá Mini** (Mô hình MVC Thuần - PHP 8.2 + MariaDB + Vanilla JS + Modern SaaS CSS).

---

## 🏗️ Tổng Quan 5 Công Cụ Kiểm Thử Trong Thư Mục `tester/`

```text
tester/
├── README.md                                  # Hướng dẫn tổng hợp & quy trình thực thi
├── 01_unit_test/                              # 🟢 TOOL 1: PHPUnit (41 Test Cases - 110 Assertions)
│   ├── CsrfTest.php                           # Test sinh mã & xác thực Anti-CSRF Token 64-hex
│   ├── PricingCalculationTest.php             # Test công thức tính giá sân 5 và sân 7 ghép
│   ├── ReputationPolicyTest.php               # Test quy tắc Điểm uy tín & điều kiện khóa tiền mặt/đặt sân
│   ├── CompoundPitchLockTest.php              # Thuật toán 2: Khóa chéo ghép sân 5 & 7 (A,B lock)
│   ├── HoldTimerAndBookingStatusTest.php      # Thuật toán 3: Giữ chỗ 10 phút & Vòng đời trạng thái
│   ├── CancellationPenaltyCalculationTest.php # Thuật toán 4: Phạt điểm uy tín theo mốc giờ hủy & hoàn tiền
│   ├── VietQRFormatTest.php                   # Thuật toán 5: Chuẩn hóa URL VietQR Napas247 MB Bank
│   ├── InputValidationAndSanitizationTest.php # Validation SĐT VN, Email, Ngày giờ, XSS Sanitization
│   └── phpunit.phar                           # File thực thi độc lập PHPUnit 10.5
├── 02_api_test/                               # 🟠 TOOL 2: Postman & Newman (23 API Test Cases)
│   ├── QuanLySanBong_API.postman_collection.json # 6 Thư mục: Auth, Discovery, Booking, Checkout, User, Admin
│   ├── QuanLySanBong.postman_environment.json    # Môi trường cấu hình Local host, user, admin credentials
│   └── README.md                              # Hướng dẫn chạy CLI qua Newman hoặc Postman GUI
├── 03_e2e_ui_test/                            # 🔵 TOOL 3: Playwright E2E UI (10 Flow Test Scenarios)
│   ├── booking.spec.js                        # Kịch bản E2E kiểm tra toàn bộ màn hình Client & Admin
│   └── playwright-report/                     # Báo cáo HTML trực quan, screenshots và traces
├── 04_load_concurrency_test/                  # 🟣 TOOL 4: Apache JMeter (3 Load & Concurrency Plans)
│   ├── RaceCondition_Booking_Test.jmx         # 50 users tranh đặt cùng 1 slot (SELECT ... FOR UPDATE)
│   ├── 02_CompoundPitch_Conflict_Test.jmx     # Tranh chấp đồng thời Sân 7 ghép vs Sân con lẻ A
│   ├── 03_HighConcurrency_ReadSearch_Test.jmx # Tải đọc cao độ 200 CCU tìm kiếm debounce & bảng giá
│   ├── test_users_data.csv                    # Dữ liệu tài khoản tham số hóa (Parameterized Data)
│   └── README.md                              # Hướng dẫn chạy JMeter Non-GUI mode & xuất HTML Dashboard
└── 05_security_test/                          # 🔴 TOOL 5: OWASP ZAP & Automated Auditor (7 Scenarios)
    ├── security_audit_runner.php              # Script PHP kiểm thử tự động 7/7 lỗ hổng an ninh mạng
    └── README.md                              # Báo cáo phân tích DAST, CVE và hướng dẫn chạy ZAP
```

---

## 🚀 Hướng Dẫn Chạy Toàn Bộ 5 Bộ Kiểm Thử

### 1️⃣ Tool 1: Unit Test (PHPUnit)
Chạy 41 ca kiểm thử đơn vị độc lập bao phủ toàn bộ thuật toán logic và chính sách:
```powershell
d:\xampp\php\php.exe tester/01_unit_test/phpunit.phar tester/01_unit_test/
```
*Kết quả: **41 / 41 passed (100%), 110 assertions**.*

---

### 2️⃣ Tool 2: API & Integration Test (Postman / Newman)
Chạy 23 kịch bản API tự động kiểm tra từ Client đến Admin:
```bash
newman run tester/02_api_test/QuanLySanBong_API.postman_collection.json -e tester/02_api_test/QuanLySanBong.postman_environment.json
```
*(Hoặc Import 2 file JSON vào ứng dụng Postman Desktop để Run Collection).*

---

### 3️⃣ Tool 3: E2E UI Test (Playwright)
Chạy 10 kịch bản kiểm thử giao diện người dùng thực tế trên Chromium:
```bash
npx playwright test tester/03_e2e_ui_test
```
Xem báo cáo HTML trực quan kèm ảnh chụp màn hình từng bước:
```bash
npx playwright show-report tester/03_e2e_ui_test/playwright-report
```

---

### 4️⃣ Tool 4: Concurrency & Load Test (Apache JMeter)
Chạy kịch bản kiểm tra Race Condition và Tải đọc 200 CCU bằng chế độ dòng lệnh:
```bash
jmeter -n -t tester/04_load_concurrency_test/RaceCondition_Booking_Test.jmx -l tester/04_load_concurrency_test/results_race.jtl
jmeter -n -t tester/04_load_concurrency_test/03_HighConcurrency_ReadSearch_Test.jmx -l tester/04_load_concurrency_test/results_read.jtl -e -o tester/04_load_concurrency_test/report_read/
```

---

### 5️⃣ Tool 5: Security Testing (OWASP ZAP & Automated Runner)
Chạy script kiểm tra tự động 7 chốt an ninh mạng cốt lõi:
```powershell
d:\xampp\php\php.exe tester/05_security_test/security_audit_runner.php
```
*Kiểm tra 100% đạt chuẩn: Prepared Statements chống SQLi, XSS Sanitization, Anti-CSRF Token, RBAC Phân quyền Admin, HttpOnly Session Cookie, và Security Headers.*
