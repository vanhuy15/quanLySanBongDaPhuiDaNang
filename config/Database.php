<?php
namespace Config;

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            // Tự động load .env nếu biến môi trường chưa được nạp
            if (getenv('DB_HOST') === false && file_exists(__DIR__ . '/../.env')) {
                require_once __DIR__ . '/../core/DotEnv.php';
                (new \Core\DotEnv(__DIR__ . '/../.env'))->load();
            }

            $host = getenv('DB_HOST') ?: '127.0.0.1';
            // Nếu host là localhost trên Linux, đổi sang 127.0.0.1 để tránh lỗi Unix socket
            if ($host === 'localhost' && DIRECTORY_SEPARATOR === '/') {
                $host = '127.0.0.1';
            }

            $port = (int)(getenv('DB_PORT') ?: 3306);
            $user = getenv('DB_USER') ?: 'root';
            $pass = (getenv('DB_PASS') !== false) ? getenv('DB_PASS') : '';
            $name = getenv('DB_NAME') ?: 'quanlysanbong';

            try {
                self::$conn = new \mysqli($host, $user, $pass, $name, $port);
            } catch (\mysqli_sql_exception $e) {
                // Fallback kết nối 127.0.0.1 nếu gặp lỗi socket
                if ($host !== '127.0.0.1') {
                    self::$conn = new \mysqli('127.0.0.1', $user, $pass, $name, $port);
                } else {
                    throw $e;
                }
            }

            if (self::$conn->connect_error) {
                die("Kết nối thất bại: " . self::$conn->connect_error);
            }
            self::$conn->set_charset("utf8mb4");
            date_default_timezone_set('Asia/Ho_Chi_Minh');
        }
        return self::$conn;
    }

    /**
     * Tự động hủy các đơn đặt sân quá 10 phút chưa hoàn tất thanh toán.
     * Dùng method này thay cho việc copy-paste query ở nhiều controller.
     */
    public static function cleanupExpiredBookings(): void {
        $conn = self::getConnection();
        $conn->query("
            UPDATE dat_san ds
            LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
            SET ds.trang_thai = 'Đã hủy'
            WHERE ds.deleted_at IS NULL
              AND (
                  (ds.trang_thai = 'Chờ thanh toán' AND TIMESTAMPDIFF(MINUTE, ds.ngay_tao, NOW()) >= 10)
                  OR (ds.trang_thai = 'Chờ xác nhận' AND tt.thanh_toan_id IS NULL AND TIMESTAMPDIFF(MINUTE, ds.ngay_tao, NOW()) >= 10)
              )
        ");

        // Đồng bộ giải phóng các slot khóa ở cấp CSDL cho các đơn đã hủy hoặc hoàn tiền
        $conn->query("
            UPDATE dat_san_slots dss
            JOIN dat_san ds ON dss.dat_san_id = ds.dat_san_id
            SET dss.trang_thai = 'released'
            WHERE ds.trang_thai IN ('Đã hủy', 'Hủy', 'Chờ hoàn tiền', 'Đã hoàn tiền')
              AND dss.trang_thai = 'active'
        ");
    }
}
