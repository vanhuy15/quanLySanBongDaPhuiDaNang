# 🏟️ Hệ Thống Đặt & Quản Lý Sân Bóng Mini (Football Pitch Booking & Management System)

> **Hệ thống đặt sân bóng mini trực tuyến toàn diện**, được xây dựng bằng **PHP (Custom MVC Pattern)** và **MySQL**, cung cấp giải pháp trọn gói cho việc quản lý sân bóng tại Việt Nam.

[![PHP](https://img.shields.io/badge/PHP-8.x-blue?logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-orange?logo=mysql)](https://www.mysql.com/)
[![Vanilla CSS](https://img.shields.io/badge/CSS-Vanilla-green)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## 📋 Giới Thiệu

Đây là ứng dụng web cho phép khách hàng đặt sân bóng trực tuyến theo từng khung giờ với sự hỗ trợ linh hoạt của sân 5 người và sân 7 người (ghép từ các sân 5). Ứng dụng cung cấp các tùy chọn thanh toán linh hoạt qua **VietQR** hoặc **Tiền mặt**. Đồng thời, hệ thống cung cấp một trang quản trị (Admin Panel) mạnh mẽ để chủ sân quản lý lịch đặt, sân bóng, người dùng, và doanh thu.

**Các điểm nổi bật về kỹ thuật:**
- 🔒 **Bảo vệ chống đặt trùng sân (Race Condition):** Xử lý đặt sân đồng thời an toàn tuyệt đối với **MySQL Transaction** và **Row-level Locking (FOR UPDATE)**.
- 🛡️ **Bảo mật toàn diện:** Tích hợp chống **CSRF (Cross-Site Request Forgery)**, ngăn chặn SQL Injection bằng **Prepared Statements**, ẩn thông tin cấu hình qua file `.env`.
- 🌟 **Hệ thống điểm uy tín (Reputation System):** Tự động theo dõi hành vi khách hàng, trừ điểm khi bùng sân và khóa tài khoản đặt sân nếu điểm dưới mức cho phép (< 40 điểm).
- 💳 **Thanh toán nhanh gọn:** Tích hợp **VietQR** thanh toán tự động xác nhận.
- 📧 **Gửi Email tự động:** Thông báo đặt sân, hủy sân và xác nhận đơn qua **PHPMailer**.

---

## ✨ Chức Năng Chi Tiết

### 👥 Dành Cho Khách Hàng (User)
- **Quản lý Tài khoản:** Đăng ký, Đăng nhập, cập nhật thông tin cá nhân.
- **Tìm kiếm & Xem Sân:** Xem danh sách sân, lọc sân bóng.
- **Đặt Sân Thông Minh:**
  - Hỗ trợ chọn **Sân 5** hoặc **Sân 7** (tự động ghép các sân 5 thành phần theo cấu hình).
  - Giao diện trực quan: chọn sân trống, hiển thị trực tiếp các sân đã được đặt.
- **Thanh toán linh hoạt:** Hỗ trợ thanh toán qua mã **VietQR** hoặc **Tiền mặt**.
- **Quản lý Đơn:** Xem lịch sử đặt sân, Hủy đặt sân và yêu cầu hoàn tiền (nếu đủ điều kiện).
- **Hệ thống Đánh giá:** Khách hàng có thể để lại Review cho sân bóng sau khi sử dụng xong.
- **Thông báo Real-time:** Nhận thông báo trong hệ thống về tình trạng đơn hàng.

### 🔧 Dành Cho Quản Trị Viên (Admin)
- **Dashboard Tổng Quan:** Hiển thị biểu đồ doanh thu, số liệu thống kê nhanh trong 7 ngày gần nhất.
- **Quản lý Đơn Đặt Sân:** Duyệt đơn, Hủy đơn, Thu tiền, Quét mã VietQR và Hoàn tiền cho khách.
- **Quản lý Sân Bóng:** Thêm, Sửa, Xóa sân (hỗ trợ cấu hình ghép sân 7 từ sân 5).
- **Quản lý Khung Giờ & Bảng Giá:** Cài đặt giá tiền khác nhau cho từng khung giờ và loại sân.
- **Quản lý Người Dùng:** Giám sát người dùng, theo dõi điểm uy tín, số lần bùng sân.
- **Báo Cáo Doanh Thu:** Xuất dữ liệu thống kê doanh thu chi tiết ra file **Excel** với các bộ lọc đa dạng.

---

## 🛠️ Công Nghệ Sử Dụng (Tech Stack)

- **Ngôn ngữ & Kiến trúc:** PHP 8.x nguyên bản, tự xây dựng **Custom MVC Framework** (Model - View - Controller). Không phụ thuộc Laravel/CodeIgniter.
- **Cơ sở dữ liệu:** MySQL 8.x.
- **Giao diện (Frontend):** HTML5, CSS3 (Vanilla), JavaScript (ES6).
- **Gửi Email:** PHPMailer kết hợp Gmail SMTP.
- **Thanh toán:** VietQR API (img.vietqr.io).
- **Môi trường chạy:** XAMPP (Apache + MySQL) hoặc bất kỳ web server PHP nào.

---

## 🏗️ Kiến Trúc Thư Mục

```text
quanlysanbong/
├── index.php                   # Điểm vào duy nhất (Entry point), Autoloader, Routing
├── .env.example                # Template cho các biến môi trường
├── database_updates.sql        # File script update cơ sở dữ liệu (tạo bảng, index)
├── core/
│   ├── Router.php              # Xử lý Request routing & dispatch
│   ├── Controller.php          # Controller gốc chứa hàm render view
│   └── DotEnv.php              # Trình đọc cấu hình file .env
├── config/
│   └── Database.php            # Kết nối DB dạng Singleton (mysqli)
├── controllers/
│   ├── AuthController.php      # Đăng nhập, Đăng ký, Đăng xuất
│   ├── HomeController.php      # Trang chủ, Tìm kiếm
│   ├── DatSanController.php    # Xử lý luồng đặt sân, hiển thị lịch, chống race condition
│   ├── UserController.php      # Trang cá nhân, lịch sử đơn
│   ├── PaymentController.php   # Xử lý thanh toán VietQR
│   ├── ReviewController.php    # Đánh giá sân bóng
│   └── NotificationController.php
├── models/
│   ├── User.php                # Model thao tác người dùng, uy tín, trạng thái
│   ├── SanBong.php             # Model quản lý sân bóng, loại sân
│   ├── KhungGio.php            # Model quản lý khung giờ, bảng giá sân 5 & 7
│   ├── DatSan.php              # Model xử lý đặt sân, Transaction & Row-locking
│   ├── Review.php              # Model quản lý đánh giá, rating
│   ├── DoanhThu.php            # Model thống kê doanh thu đa chiều, biểu đồ
│   └── Notification.php        # Model thao tác thông báo
├── views/
│   ├── auth/                   # Giao diện Đăng nhập, Đăng ký
│   ├── home/                   # Giao diện Trang chủ
│   ├── datsan/                 # Giao diện Đặt sân / Lịch
│   ├── payment/                # Giao diện Thanh toán
│   └── user/                   # Giao diện Hồ sơ cá nhân
├── admin/                      # Khu vực Quản trị Admin
│   ├── datsan/                 # Quản lý đơn
│   ├── sanbong/                # Quản lý sân
│   ├── khunggio/               # Quản lý giờ và giá
│   ├── user/                   # Quản lý người dùng
│   └── doanhthu/               # Báo cáo doanh thu & Xuất Excel
├── includes/
│   ├── PHPMailer/              # Thư viện gửi email
│   ├── send_mail.php           # Helper gửi mail
│   ├── email_templates.php     # Template HTML email
│   └── csrf.php                # Helper bảo mật CSRF Token
└── assets/ / images/           # File tĩnh (CSS, JS, hình ảnh sân, avatar)
```

---

## 🚀 Hướng Dẫn Cài Đặt (Local Development)

### 1. Yêu cầu hệ thống
- PHP >= 8.0
- MySQL >= 8.0
- Web Server: XAMPP, Laragon, WAMP hoặc Apache/Nginx.
- Tài khoản Gmail có bật **Mật khẩu ứng dụng (App Password)** để gửi email.

### 2. Cài đặt Source Code
```bash
# Clone project
git clone https://github.com/YOUR_USERNAME/quanlysanbong.git
cd quanlysanbong
```
- Sao chép toàn bộ mã nguồn vào thư mục root của Web Server (ví dụ: `C:/xampp/htdocs/quanlysanbong/`).

### 3. Cấu hình Cơ Sở Dữ Liệu
- Mở **phpMyAdmin** (thường tại `http://localhost/phpmyadmin`).
- Tạo database mới với tên: `quanlysanbong`.
- Import file SQL chứa cấu trúc CSDL vào.
- Chạy thêm file script `database_updates.sql` nếu đây là bản cập nhật mới.

### 4. Thiết lập Biến Môi Trường (.env)
- Trong thư mục gốc, copy file `.env.example` thành `.env`:
```bash
cp .env.example .env
```
- Mở file `.env` và điền thông tin thực tế:
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=quanlysanbong

MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=your_gmail_app_password
MAIL_ADMIN_NOTIFY=admin_email_to_receive_notifs@gmail.com
```

### 5. Chạy Trải Nghiệm
- Mở trình duyệt và truy cập: `http://localhost/quanlysanbong/`

---

## 👤 Tài Khoản Demo (Mặc định)

| Phân quyền | Username | Password |
|---|---|---|
| **Quản trị viên (Admin)** | admin | admin123 |
| **Khách hàng** | khachhang1 | 123456 |

> ⚠️ **Lưu ý:** Hãy đổi mật khẩu Admin ngay sau khi cài đặt thành công!

---

## 🔮 Hướng Phát Triển Tương Lai
- [ ] Tích hợp cổng thanh toán VNPay / MoMo thực sự qua API.
- [ ] Bổ sung tính năng khôi phục mật khẩu (Quên mật khẩu) qua Email với Token.
- [ ] Bổ sung Rate Limiting cho API đăng nhập để chống brute-force.
- [x] Chuẩn hóa toàn bộ kiến trúc MVC và tầng Model (Repository Pattern).
- [x] Xây dựng bộ công cụ kiểm thử toàn diện (PHPUnit, Playwright, JMeter, Postman, ZAP).

---

## 📄 Bản Quyền (License)

Dự án này được cấp phép theo **MIT License**. Bạn có thể thoải mái sử dụng, sửa đổi và phân phối. Chi tiết xem tại file [LICENSE](LICENSE).
