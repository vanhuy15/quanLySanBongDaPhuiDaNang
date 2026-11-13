# PitchManager - Developer & Agent Guidelines (AGENTS.md)

Tài liệu quy chuẩn kiến trúc, luồng nghiệp vụ và an toàn kỹ thuật cho dự án **Quản Lý Sân Bóng Đá Phủi Đà Nẵng**. Mọi AI Agent và lập trình viên khi bảo trì/phát triển dự án cần tuân thủ nghiêm ngặt các nguyên tắc dưới đây.

---

## 🏛️ 1. Nguyên Tắc Kiến Trúc & Công Nghệ

1. **Pure PHP MVC (Không phụ thuộc framework):**
   - Mã nguồn tuân thủ mô hình Model-View-Controller thuần.
   - Tuyệt đối không tự ý cài đặt framework nặng (Laravel, Symfony, CodeIgniter) làm phá vỡ cấu trúc tinh gọn.
   - Frontend không dùng inline Javascript / inline CSS lộn xộn trong Controller hoặc Model.

2. **Chống Race Condition & Đặt trùng sân (Double-Booking):**
   - Nghiệp vụ đặt sân cốt lõi bắt buộc dùng giao dịch CSDL (`$conn->begin_transaction()`) kết hợp khóa mức hàng:
     ```sql
     SELECT dat_san_id, ten_san_con, san_con_khoa FROM dat_san WHERE ... FOR UPDATE
     ```
   - Khóa cứng hai tầng ở cấp CSDL qua bảng `dat_san_slots` với ràng buộc `UNIQUE KEY active_key (khung_gio_id, ngay_dat, ten_san_con, trang_thai)`.
   - Khi hủy đơn hoặc hoàn tiền (`Đã hủy`, `Hủy`, `Chờ hoàn tiền`, `Đã hoàn tiền`), **bắt buộc cập nhật `dat_san_slots.trang_thai = 'released'`** để giải phóng slot cho khách hàng khác.

3. **Ràng Buộc Thời Gian & Chống Đặt Quá Giờ:**
   - Dự án được bắt đầu từ năm 2025.
   - Toàn bộ bộ lọc ngày, lịch đặt, báo cáo doanh thu bắt buộc có giới hạn dưới:
     - Frontend: `min="2025-01-01"` (Client đặt sân: `min="<?= date('Y-m-d') ?>"`).
     - Backend: Kiểm tra và chuẩn hóa ngày `$date >= '2025-01-01'`.
   - **Khoảng đệm chuẩn bị sân (Lead Time Buffer 5 phút):** Khách hàng không thể đặt các khung giờ đã trôi qua hoặc cách giờ bắt đầu dưới 5 phút (`slot_start_time <= time() + 300`). Cả Frontend (làm mờ, nút disabled) và Backend (chặn Controller & Model) đều bắt buộc thực thi.
   - **Xử lý đơn quá hạn phía Admin:** Nếu đơn `Chờ xác nhận` bằng VietQR quá giờ bắt đầu mà Admin chưa duyệt, hệ thống tự động chuyển sang `Chờ hoàn tiền`, giải phóng slot và bảo lưu 100% điểm uy tín của khách. Đơn tiền mặt tự động chuyển sang `Đã hủy`.

---

## 🛡️ 2. Bảo Mật & Quản Trị Hệ Thống

1. **Phòng chống Lộ Thông Tin (Information Disclosure):**
   - **Tuyệt đối không hiển thị trực tiếp `$e->getMessage()`, `$conn->error`, `$stmt->error`** ra giao diện người dùng.
   - Mọi lỗi CSDL phải được ghi vào log nội bộ có cấu trúc qua `\Core\Logger::error(...)` và hiển thị thông báo chung an toàn cho người dùng.

2. **Xác thực Đăng nhập Phân biệt Hoa / Thường (Case Sensitive):**
   - Tên đăng nhập (`ten_dang_nhap`) bắt buộc so sánh chính xác từng ký tự chữ thường / chữ hoa bằng cú pháp `BINARY ten_dang_nhap = ?` ở cấp SQL và toán tử `===` ở cấp PHP, tuyệt đối không cho phép đăng nhập nhầm hoa thường (`ADMIN15` != `admin15`).

3. **CSRF & SQL Injection:**
   - Mọi request `POST` bắt buộc phải kèm mã xác thực `csrf_token` (qua `verify_csrf_token()`).
   - 100% câu truy vấn có dữ liệu từ người dùng bắt buộc dùng Prepared Statements (`$conn->prepare()`, `bind_param()`).

4. **Chính Sách Điểm Uy Tín (Reputation System):**
   - Thang điểm chuẩn: 0 đến 100 điểm. Mặc định khách mới: 100đ.
   - Bùng sân không đến đá: Trừ 25 điểm (`bung_san`), tăng `so_lan_bung_san`.
   - Hủy sát giờ (< 4h): Trừ 15 điểm; Hủy sớm (< 12h): Trừ 5 điểm.
   - Hoàn thành trận đấu đúng giờ: Tự động cộng +5 điểm.
   - Gửi đánh giá / nhận xét 1-5 sao: Cộng +2 điểm.
   - Chế tài: Dưới 60 điểm khóa phương thức Tiền mặt; Dưới 40 điểm khóa quyền đặt sân.

---

## 🧪 3. Quy Trình Kiểm Thử & CI/CD

Trước khi commit bất kỳ tính năng nào, phải chạy và đảm bảo vượt qua 100% test suite:
- Kiểm thử đơn vị: `npm run test:unit` (PHPUnit 10.5, 49 tests passed)
- Kiểm thử bảo mật: `npm run test:security` (OWASP Automated Security Runner, 9/9 passed)
- Kiểm thử API: `npm run test:api` (Newman / Postman)
- Kiểm thử giao diện: `npm run test:e2e` (Playwright)

