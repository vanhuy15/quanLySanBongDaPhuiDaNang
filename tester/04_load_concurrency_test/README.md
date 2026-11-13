# ⚡ Kiểm Thử Tải & Tranh Chấp Đồng Thời (Performance & Concurrency - Apache JMeter)

Bộ kịch bản kiểm thử hiệu năng và an toàn giao dịch đồng thời cho hệ thống Quản lý Đặt Sân Bóng Đá bằng **Apache JMeter 5.6+**.

---

## 🎯 1. Danh Sách Các Kịch Bản Kiểm Thử (JMeter Test Plans)

### 📁 Kịch bản 1: `RaceCondition_Booking_Test.jmx` (Chống đặt trùng slot đơn)
- **Mục tiêu**: Đưa 50 luồng người dùng đồng thời gửi yêu cầu đặt **cùng 1 sân con** trong **cùng 1 khung giờ** và **cùng 1 ngày**.
- **Kỳ vọng**:
  - Nhờ cơ chế MySQL Transaction kết hợp `SELECT ... FOR UPDATE`, **duy nhất 1 request đầu tiên được duyệt thành công (HTTP 200/302)**.
  - 49 request còn lại phải nhận thông báo lỗi xung đột: *"Khung giờ này vừa có người đặt"*, hoàn toàn không xảy ra tình trạng Double-Booking.

### 📁 Kịch bản 2: `02_CompoundPitch_Conflict_Test.jmx` (Xung đột Sân 7 ghép & Sân 5 con)
- **Mục tiêu**: 
  - Nhóm 1 (20 threads) gửi yêu cầu đặt Sân 7 ghép (khóa 2 sân con A, B).
  - Nhóm 2 (20 threads) gửi yêu cầu đặt lẻ Sân con A.
- **Kỳ vọng**:
  - Khi một bên thành công, bên còn lại lập tức bị từ chối do xung đột thuộc tính `san_con_khoa`.
  - Tổng số đơn thành công trên toàn bộ cụm sân ghép luôn $\le 1$.

### 📁 Kịch bản 3: `03_HighConcurrency_ReadSearch_Test.jmx` (Tải đọc cao độ 200 CCU)
- **Mục tiêu**: Giả lập 200 người dùng truy cập liên tục trong 5 giây, tìm kiếm theo từ khóa debounce, lọc theo khoảng giá và xem ma trận lịch AJAX.
- **Kỳ vọng**:
  - Tỷ lệ lỗi (Error Rate): **0.00%**.
  - Thời gian phản hồi trung bình (Average Response Time): **< 200ms**.
  - Không gây nghẽn tiến trình Apache / MySQL PHP-FPM.

---

## 🚀 2. Hướng Dẫn Chạy Test Bằng Dòng Lệnh (CLI Non-GUI Mode)

Để có kết quả đo chính xác nhất mà không tốn RAM cho giao diện, chạy bằng terminal:

```bash
# 1. Chạy kiểm thử Race Condition
jmeter -n -t tester/04_load_concurrency_test/RaceCondition_Booking_Test.jmx -l tester/04_load_concurrency_test/results_race.jtl -e -o tester/04_load_concurrency_test/report_race/

# 2. Chạy kiểm thử xung đột sân ghép 5 & 7
jmeter -n -t tester/04_load_concurrency_test/02_CompoundPitch_Conflict_Test.jmx -l tester/04_load_concurrency_test/results_compound.jtl

# 3. Chạy kiểm thử tải đọc 200 CCU và xuất báo cáo Dashboard HTML
jmeter -n -t tester/04_load_concurrency_test/03_HighConcurrency_ReadSearch_Test.jmx -l tester/04_load_concurrency_test/results_read.jtl -e -o tester/04_load_concurrency_test/report_read/
```

Sau khi chạy xong, mở file `report_read/index.html` bằng trình duyệt để xem biểu đồ chi tiết: Throughput, Latency, Response Time Percentiles (90th, 95th, 99th).

