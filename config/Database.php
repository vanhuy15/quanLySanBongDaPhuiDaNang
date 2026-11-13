<?php
namespace Config;

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $name = getenv('DB_NAME') ?: 'quanlysanbong';

            self::$conn = new \mysqli($host, $user, $pass, $name);

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
        self::getConnection()->query("
            UPDATE dat_san ds
            LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
            SET ds.trang_thai = 'Đã hủy'
            WHERE ds.deleted_at IS NULL
              AND (
                  (ds.trang_thai = 'Chờ thanh toán' AND TIMESTAMPDIFF(MINUTE, ds.ngay_tao, NOW()) >= 10)
                  OR (ds.trang_thai = 'Chờ xác nhận' AND tt.thanh_toan_id IS NULL AND TIMESTAMPDIFF(MINUTE, ds.ngay_tao, NOW()) >= 10)
              )
        ");
    }
}
