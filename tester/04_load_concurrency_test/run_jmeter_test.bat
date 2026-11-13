@echo off
chcp 65001 > nul
echo =======================================================
echo   CHẠY KIỂM THỬ TẢI & TRANH CHẤP ĐỒNG THỜI (JMETER)
echo =======================================================
echo.

where jmeter >nul 2>nul
if %errorlevel% neq 0 (
    echo [CẢNH BÁO] Không tìm thấy lệnh 'jmeter' trên PATH hệ thống.
    echo Vui lòng tải Apache JMeter từ https://jmeter.apache.org/download_jmeter.cgi
    echo và thêm thư mục bin của JMeter vào biến môi trường PATH.
    echo.
    pause
    exit /b 1
)

echo [1/3] Đang chạy kiểm thử Race Condition 50 người tranh đặt 1 sân...
jmeter -n -t RaceCondition_Booking_Test.jmx -l results_race.jtl -e -o report_race/

echo.
echo [2/3] Đang chạy kiểm thử Xung đột Ghép sân 5 và Sân 7...
jmeter -n -t 02_CompoundPitch_Conflict_Test.jmx -l results_compound.jtl

echo.
echo [3/3] Đang chạy kiểm thử Tải đọc 200 CCU và tạo Dashboard HTML...
jmeter -n -t 03_HighConcurrency_ReadSearch_Test.jmx -l results_read.jtl -e -o report_read/

echo.
echo =======================================================
echo   HOÀN TẤT KIỂM THỬ JMETER!
echo   Báo cáo trực quan Dashboard HTML đã được xuất ra:
echo   - report_race/index.html
echo   - report_read/index.html
echo =======================================================
pause
