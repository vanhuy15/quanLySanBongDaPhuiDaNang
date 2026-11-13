# 🏟️ Hệ Thống Đặt & Quản Lý Sân Bóng Mini Trực Tuyến (Football Pitch Booking & Management System)

> **Giải pháp chuyển đổi số toàn diện cho chuỗi sân bóng đá mini & phủi tại Việt Nam.**  
> Xây dựng theo kiến trúc **Pure MVC (Mô hình MVC Thuần không phụ thuộc framework)**, tối ưu hiệu năng cao, bảo mật đa tầng và tích hợp sẵn **5 bộ công cụ kiểm thử phần mềm chuyên nghiệp** (PHPUnit, Postman, Playwright, Apache JMeter, OWASP ZAP).

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-10.5-377755?style=for-the-badge&logo=php&logoColor=white)](https://phpunit.de/)
[![Playwright](https://img.shields.io/badge/Playwright-E2E-2EAD33?style=for-the-badge&logo=playwright&logoColor=white)](https://playwright.dev/)
[![Postman](https://img.shields.io/badge/Postman-API_Tests-FF6C37?style=for-the-badge&logo=postman&logoColor=white)](https://www.postman.com/)
[![Apache JMeter](https://img.shields.io/badge/Apache_JMeter-Concurrency_Test-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://jmeter.apache.org/)
[![OWASP ZAP](https://img.shields.io/badge/OWASP-Security_Audit-005E8A?style=for-the-badge&logo=owasp&logoColor=white)](https://www.zaproxy.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

---

## 🌟 ĐIỂM SÁNG KỸ THUẬT & THUẬT TOÁN CỐT LÕI

1. ⚡ **Khóa dòng chống Đặt trùng sân (Race Condition Prevention):**  
   Ứng dụng cơ chế **MySQL Transaction** kết hợp khóa mức hàng **`SELECT ... FOR UPDATE`**. Khi có hàng chục khách hàng cùng chọn 1 sân trong cùng 1 khung giờ, hệ thống đảm bảo chính xác tuyệt đối **chỉ duy nhất 1 đơn đặt thành công**, triệt tiêu 100% rủi ro Double-Booking.
2. 🔄 **Thuật toán Ghép Sân Đa Năng (Flexible Compound Pitch Merging):**  
   Hỗ trợ cấu hình sân 7 người ghép từ các sân 5 người thành phần. Khi sân 7 được đặt, toàn bộ các sân 5 tương ứng tự động chuyển sang trạng thái bị khóa (`san_con_khoa = 'A,B'`) và ngược lại.
3. ⏱️ **Bộ đếm giữ chỗ 10 phút & Tự động nhả slot (10-Minute Hold & Auto-Release):**  
   Tự động khóa tạm thời slot sân trong 10 phút khi khách hàng chuyển sang bước thanh toán. Nếu quá thời gian mà chưa hoàn tất, tiến trình nền tự động giải phóng vị trí cho khách hàng khác.
4. 🛡️ **Hệ thống Điểm Uy Tín Khách Hàng (Customer Credit Score System):**  
   Tự động tính điểm uy tín dựa trên hành vi: cộng điểm khi hoàn thành trận/đánh giá (+2đ), trừ điểm khi hủy sát giờ (-5đ đến -15đ), chế tài nặng khi bùng sân (-25đ). Hệ thống tự động khóa phương thức Tiền mặt nếu điểm $< 60$đ và khóa quyền đặt sân nếu điểm $< 40$đ.
5. 💳 **Thanh toán Tự Động VietQR Chuẩn Napas 247:**  
   Sinh mã QR thanh toán động theo chuẩn ngân hàng (MB Bank, STK `1111000050174`), tự động điền sẵn số tiền và nội dung chuyển khoản `DATSAN <id>` hỗ trợ nút sao chép thông minh.
6. 🧩 **Kiến trúc Modular MVC Thuần:**  
   Toàn bộ mã nguồn được tách biệt nghiêm ngặt: 0% inline CSS/JS trong View, giao diện Admin và Client được tổ chức thành các module stylesheet (`admin/css/`, `assets/css/modules/`) và file JavaScript chuyên biệt.

---

## 📋 TỔNG HỢP CHỨC NĂNG HỆ THỐNG

### 👥 1. Phân Hệ Khách Hàng (Client Portal)
* **Khám phá & Tìm kiếm:** Tìm kiếm thời gian thực (Debounce Search), lọc đa tiêu chí theo loại sân (Sân 5 / Sân 7) và khoảng giá linh hoạt.
* **Sơ đồ chọn sân trực quan:** Bản đồ sân bóng 2D, tra cứu lịch theo ngày và khung giờ thời gian thực qua AJAX.
* **Cổng thanh toán đa dạng:** Tùy chọn Tiền mặt tại sân hoặc Quét mã VietQR tiện lợi.
* **Lịch sử & Hủy đơn hoàn tiền:** Theo dõi toàn bộ lịch sử đơn đặt, gửi yêu cầu hủy đơn và cung cấp STK nhận hoàn tiền qua ngân hàng.
* **Hồ sơ cá nhân & Bảng điểm uy tín:** Theo dõi thứ hạng uy tín (VIP, Tiêu chuẩn, Cảnh báo, Khóa), xem chi tiết lịch sử biến động điểm.
* **Đánh giá & Xếp hạng:** Đánh giá chất lượng mặt cỏ, ánh sáng, dịch vụ từ 1 đến 5 sao (+2 điểm uy tín).

### 🛠️ 2. Phân Hệ Quản Trị Viên (Admin Portal)
* **KPI Dashboard thời gian thực:** Thống kê doanh thu 7 ngày qua biểu đồ trực quan, theo dõi đơn chờ duyệt, tổng số sân và tỷ lệ lấp đầy.
* **Quản lý Đặt sân tập trung:** Duyệt đơn, từ chối đơn, xác nhận thu tiền mặt, báo cáo khách bùng sân, quét mã VietQR hoàn tiền cho khách.
* **Quản lý Sân bóng & Cấu hình ghép:** Thêm mới, chỉnh sửa thông tin sân, cấu hình quan hệ ghép sân 5 và sân 7 linh hoạt.
* **Quản lý Khung giờ & Bảng giá:** Thiết lập bảng giá riêng cho giờ vàng/cuối tuần, tùy chỉnh giá sân ghép.
* **Quản lý Người dùng:** Theo dõi hành vi khách hàng, can thiệp khôi phục hoặc điều chỉnh điểm uy tín thủ công.
* **Báo cáo Doanh thu & Xuất Excel:** Lọc doanh thu theo ngày/tháng/sân và xuất báo cáo bảng tính Excel chuẩn định dạng.

---

## 🧪 BỘ KIỂM THỬ PHẦN MỀM CHUYÊN NGHIỆP (`tester/`)

Dự án tích hợp đầy đủ quy trình kiểm thử theo tiêu chuẩn Software QA:

```text
tester/
├── 01_unit_test/              # Kiểm thử Hộp Trắng (PHPUnit 10.5): 41 Tests, 110 Assertions
├── 02_api_test/                # Kiểm thử API & Tích hợp (Postman & Newman): 23 API Test Cases
├── 03_e2e_ui_test/             # Kiểm thử Tự động Giao diện (Playwright E2E): 10 Luồng nghiệp vụ
├── 04_load_concurrency_test/   # Kiểm thử Tải & Race Condition (Apache JMeter 5.6): 3 Test Plans
└── 05_security_test/           # Kiểm thử An toàn Thông tin (OWASP ZAP & Automated Auditor)
```

### ⚡ Các Lệnh Terminal Chạy Test Nhanh (NPM Scripts)

```bash
# 1. Kiểm thử đơn vị & thuật toán cốt lõi (PHPUnit)
npm run test:unit

# 2. Kiểm thử tự động API Client & Admin (Newman CLI)
npm run test:api

# 3. Kiểm thử tự động toàn diện giao diện (Playwright Headless)
npm run test:e2e

# 4. Kiểm thử giao diện có mở trình duyệt Chromium trực quan
npm run test:e2e:headed

# 5. Mở bảng báo cáo HTML Playwright kèm video / trace / screenshot
npm run test:report

# 6. Kiểm thử tự động 7 chốt an ninh mạng OWASP Top 10
npm run test:security

# 7. Chạy liên hoàn toàn bộ test suite
npm run test:all
```

---

## 📂 KIẾN TRÚC MÃ NGUỒN (DIRECTORY STRUCTURE)

```text
quanlysanbong/
├── index.php                          # Entry point duy nhất, Autoloader, Global Middleware
├── .env.example                       # Biến môi trường mẫu (Database, Mail, VietQR)
├── .gitignore                         # Bảo vệ thông tin bí mật, loại bỏ .env & artifacts
├── config/
│   └── Database.php                   # Kết nối CSDL MySQL Singleton & dọn dẹp đơn hết hạn
├── core/
│   ├── Router.php                     # Bộ định tuyến request an toàn, lọc ký tự độc
│   ├── Controller.php                 # Base Controller cung cấp render & jsonResponse
│   └── DotEnv.php                     # Bộ phân tích nạp file cấu hình .env
├── controllers/                       # 8 Controllers quản lý toàn bộ phân hệ Client & Admin
├── models/                            # 7 Models chuẩn hóa truy vấn CSDL qua Prepared Statements
├── views/                             # Giao diện người dùng thuần HTML/PHP, phân chia theo module
├── admin/                             # Phân hệ Quản trị viên (Dashboard, Đặt sân, Sân, Khách hàng)
│   ├── css/                           # 7 file CSS modular tách biệt (base, components, tables...)
│   └── js/                            # JavaScript điều khiển AJAX, biểu đồ Chart.js, Modal
├── assets/                            # Tài nguyên tĩnh phía Client
│   ├── css/modules/                   # 6 file CSS modular cho giao diện Client
│   └── js/                            # Script xử lý tìm kiếm debounce, chọn lịch 2D, thanh toán
├── includes/                          # Header, Footer, Menu, CSRF Guard, PHPMailer
└── tester/                            # 5 Công cụ kiểm thử phần mềm chuyên nghiệp
```

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT & CHẠY DỰ ÁN

### 1. Yêu Cầu Môi Trường
* **PHP**: `>= 8.2` (Bật các extension: `mysqli`, `curl`, `mbstring`, `openssl`).
* **Cơ sở dữ liệu**: MySQL / MariaDB (XAMPP khuyến nghị).
* **Node.js**: `>= 18.x` (Dùng để chạy Playwright và Newman).

### 2. Các Bước Cài Đặt

1. **Clone mã nguồn về máy:**
   ```bash
   git clone https://github.com/vanhuy15/quanLySanBongDaPhuiDaNang.git
   cd quanLySanBongDaPhuiDaNang
   ```

2. **Cấu hình môi trường (.env):**
   Copy file `.env.example` thành `.env` và điền cấu hình CSDL của bạn:
   ```bash
   cp .env.example .env
   ```

3. **Cài đặt thư viện kiểm thử (Playwright & Newman):**
   ```bash
   npm install
   npx playwright install chromium
   ```

4. **Khởi chạy ứng dụng:**
   * Mở XAMPP và Start **Apache** + **MySQL**.
   * Truy cập ứng dụng trên trình duyệt: `http://localhost/quanlysanbong/`
   * Tài khoản Quản trị mặc định: `admin15` / Mật khẩu: `123456`
   * Tài khoản Khách hàng thử nghiệm: `testuser` / Mật khẩu: `123456`

---

## 👨‍💻 TÁC GIẢ & BẢN QUYỀN

* **Tác giả:** Hoàng Văn Huy ([@vanhuy15](https://github.com/vanhuy15))
* **Email:** alexanderhuy2@gmail.com
* **GitHub Repository:** [quanLySanBongDaPhuiDaNang](https://github.com/vanhuy15/quanLySanBongDaPhuiDaNang)
* **Giấy phép:** [MIT License](LICENSE)
