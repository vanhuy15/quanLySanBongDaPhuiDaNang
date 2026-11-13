# 🛡️ Kiểm Thử Bảo Mật Ứng Dụng (Security Testing - OWASP ZAP & Automated Auditor)

Hệ thống kiểm thử an ninh mạng toàn diện cho ứng dụng Quản lý Sân bóng theo chuẩn **OWASP Top 10**.

---

## 📊 1. Bảng Tổng Hợp 7 Kịch Bản Kiểm Thử An Toàn (Security Test Scenarios)

| Mã | Hạng mục an ninh (OWASP) | Cơ chế phòng thủ trong mã nguồn | Trạng thái |
| :--- | :--- | :--- | :---: |
| **SEC-01** | **SQL Injection (SQLi)** | 100% Prepared Statements (`$conn->prepare`), bind param kiểu dữ liệu nghiêm ngặt | ✅ **PASSED** |
| **SEC-02** | **Cross-Site Scripting (XSS)** | Khử khuẩn bằng `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` trên toàn bộ Router & Views | ✅ **PASSED** |
| **SEC-03** | **Cross-Site Request Forgery (CSRF)** | Kiểm tra Anti-CSRF Token 64 hex characters toàn cục tại `index.php` cho mọi yêu cầu POST | ✅ **PASSED** |
| **SEC-04** | **Phân quyền truy cập (RBAC / Auth)** | Chặn khách vãng lai và tài khoản người dùng thường truy cập trái phép phân hệ `/admin/` | ✅ **PASSED** |
| **SEC-05** | **Security Headers (Clickjacking)** | `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy` | ✅ **PASSED** |
| **SEC-06** | **An toàn Phiên làm việc (Session)** | Cookie `PHPSESSID` thiết lập cờ `HttpOnly = true` và `SameSite = Lax` chống đánh cắp | ✅ **PASSED** |
| **SEC-07** | **Che giấu thông tin (Info Disclosure)** | Đã gỡ bỏ header `X-Powered-By` và kiểm soát hiển thị lỗi PHP trên môi trường Production | ✅ **PASSED** |

---

## 🚀 2. Hướng Dẫn Chạy Kiểm Thử Bảo Mật

### Cách 1: Chạy công cụ kiểm thử tự động (Automated Security Runner)
Chạy script kiểm tra trực tiếp qua CLI:
```bash
d:\xampp\php\php.exe tester/05_security_test/security_audit_runner.php
```
Kết quả hiển thị trạng thái của cả 7 lỗ hổng với thời gian phản hồi dưới 1 giây.

### Cách 2: Quét DAST bằng phần mềm OWASP ZAP
1. Khởi động Apache và MySQL trên XAMPP.
2. Mở phần mềm **OWASP ZAP 2.17.0+**.
3. Chọn thẻ **Automated Scan**, nhập URL: `http://localhost/quanlysanbong/`
4. Bấm **Attack**.
5. Sau khi quét xong, vào menu **Report** $\rightarrow$ **Generate Report...** để xuất báo cáo PDF / HTML.
