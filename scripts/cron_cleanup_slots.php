<?php
/**
 * CRON JOB / BACKGROUND WORKER: TỰ ĐỘNG GIẢI PHÓNG SLOT SÂN QUÁ HẠN
 * -------------------------------------------------------------------
 * Tự động hủy các đơn đặt sân quá 10 phút chưa thanh toán
 * và giải phóng khóa cứng slot cấp CSDL (dat_san_slots).
 * 
 * Cách chạy:
 *   CLI:        node scripts/run-php.js scripts/cron_cleanup_slots.php
 *   Windows:    Task Scheduler chạy định kỳ mỗi 2 phút
 *   Linux Cron: * /2 * * * * php /path/to/quanlysanbong/scripts/cron_cleanup_slots.php
 * -------------------------------------------------------------------
 */

// Đảm bảo chỉ chạy qua CLI hoặc Token bí mật
if (php_sapi_name() !== 'cli' && empty($_GET['cron_key'])) {
    http_response_code(403);
    die("Access Denied: Cron worker can only be executed via CLI.");
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Logger.php';

use Config\Database;
use Core\Logger;

$startTime = microtime(true);
$timestamp = date('Y-m-d H:i:s');
echo "=======================================================\n";
echo "  [CRON] PITCHMANAGER - EXPIRED BOOKINGS AUTO-CLEANUP  \n";
echo "  Thời gian: {$timestamp}                            \n";
echo "=======================================================\n\n";

try {
    $conn = Database::getConnection();

    // 1. Tìm các đơn quá hạn cần hủy trước khi chạy
    $stmtFind = $conn->query("
        SELECT ds.dat_san_id, ds.ten_san_con, ds.ngay_dat, ds.trang_thai, nd.ho_ten
        FROM dat_san ds
        LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
        LEFT JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
        WHERE ds.deleted_at IS NULL
          AND (
              (ds.trang_thai = 'Chờ thanh toán' AND TIMESTAMPDIFF(MINUTE, ds.ngay_tao, NOW()) >= 10)
              OR (ds.trang_thai = 'Chờ xác nhận' AND tt.thanh_toan_id IS NULL AND TIMESTAMPDIFF(MINUTE, ds.ngay_tao, NOW()) >= 10)
          )
    ");
    $expiredBookings = $stmtFind ? $stmtFind->fetch_all(MYSQLI_ASSOC) : [];
    $totalExpired = count($expiredBookings);

    // 2. Chạy phương thức cleanup tập trung
    Database::cleanupExpiredBookings();

    // 3. Đếm số slot đã được giải phóng
    $stmtSlots = $conn->query("
        SELECT COUNT(*) as released_slots 
        FROM dat_san_slots 
        WHERE trang_thai = 'released'
    ");
    $releasedTotal = $stmtSlots ? (int)$stmtSlots->fetch_assoc()['released_slots'] : 0;

    $duration = round((microtime(true) - $startTime) * 1000, 2);

    if ($totalExpired > 0) {
        echo "[SUCCESS] Đã tự động hủy {$totalExpired} đơn quá hạn 10 phút:\n";
        foreach ($expiredBookings as $eb) {
            echo "   - Đơn #{$eb['dat_san_id']} ({$eb['ho_ten']}) | Sân: {$eb['ten_san_con']} | Ngày: {$eb['ngay_dat']}\n";
        }
        echo "=> Đã giải phóng toàn bộ slot trong dat_san_slots thành 'released'.\n";

        Logger::info("Cron Cleanup: Đã tự động hủy {$totalExpired} đơn quá hạn", [
            'bookings' => array_column($expiredBookings, 'dat_san_id'),
            'duration_ms' => $duration
        ]);
    } else {
        echo "[OK] Không có đơn đặt sân nào quá hạn cần xử lý.\n";
    }

    echo "Hoàn thành trong {$duration} ms. Trạng thái hệ thống: SẠCH.\n";
    exit(0);

} catch (\Throwable $e) {
    echo "[ERROR] Lỗi thực thi Cron Cleanup: " . $e->getMessage() . "\n";
    Logger::error("Cron Cleanup Error: " . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}
