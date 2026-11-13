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
     * Tự động dọn dẹp và xử lý các đơn đặt sân quá hạn:
     * 1. Hủy các đơn quá 10 phút chưa thanh toán.
     * 2. Tự động xử lý các đơn quá giờ bắt đầu mà Admin chưa duyệt:
     *    - VietQR/Chuyển khoản: Tự động chuyển 'Chờ hoàn tiền', bảo lưu điểm uy tín, tạo thông báo cho khách.
     *    - Tiền mặt: Tự động chuyển 'Đã hủy'.
     * 3. Đồng bộ giải phóng các slot khóa ở cấp CSDL (dat_san_slots -> released).
     * 4. Tự động cộng +5 điểm uy tín cho các trận đấu đã đá xong.
     */
    public static function cleanupExpiredBookings(): void {
        $conn = self::getConnection();

        // 1. Tự động hủy các đơn quá 10 phút chưa hoàn tất thanh toán
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

        // 2. Tự động xử lý đơn quá giờ thi đấu mà Admin chưa duyệt
        // 2a. Đơn Chuyển khoản / VietQR (Khách đã thanh toán): Chuyển Chờ hoàn tiền
        $stale_qr = $conn->query("
            SELECT ds.dat_san_id, ds.nguoi_dung_id, sb.ten_san, ds.ngay_dat, kg.gio_bat_dau
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            JOIN san_bong sb ON kg.san_id = sb.san_id
            JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
            WHERE ds.trang_thai = 'Chờ xác nhận'
              AND tt.phuong_thuc = 'chuyen_khoan'
              AND ds.deleted_at IS NULL
              AND CONCAT(ds.ngay_dat, ' ', kg.gio_bat_dau) <= NOW()
        ");

        if ($stale_qr && $stale_qr->num_rows > 0) {
            while ($stale = $stale_qr->fetch_assoc()) {
                $b_id = (int)$stale['dat_san_id'];
                $u_id = (int)$stale['nguoi_dung_id'];
                $rf_note = json_encode([
                    'ngan_hang' => 'Chờ khách bổ sung hoặc Admin liên hệ hoàn',
                    'stk' => 'Chưa cung cấp',
                    'ten_chu_tk' => '',
                    'ly_do' => 'Hệ thống tự động hủy do Quản trị viên chưa duyệt đơn trước giờ thi đấu. Cần hoàn tiền!',
                    'thoi_gian_yeu_cau' => date('Y-m-d H:i:s'),
                    'auto_expired' => true
                ], JSON_UNESCAPED_UNICODE);

                $stmt_up = $conn->prepare("UPDATE dat_san SET trang_thai = 'Chờ hoàn tiền', thong_tin_hoan_tien = ? WHERE dat_san_id = ?");
                $stmt_up->bind_param("si", $rf_note, $b_id);
                $stmt_up->execute();
                $stmt_up->close();

                // Tạo thông báo cho khách hàng
                $noti_stmt = $conn->prepare("
                    INSERT INTO thong_bao (nguoi_dung_id, tieu_de, noi_dung, loai, lien_ket) 
                    VALUES (?, ?, ?, 'dat_san', 'index.php?controller=User&action=history')
                ");
                $title = "Đơn #PM-$b_id được chuyển sang mục Hoàn tiền";
                $content = "Đơn đặt sân " . $stale['ten_san'] . " ngày " . date('d/m/Y', strtotime($stale['ngay_dat'])) . " đã quá giờ thi đấu mà quản trị viên chưa xác nhận. Hệ thống đã chuyển đơn sang mục Hoàn tiền để bảo vệ quyền lợi của bạn.";
                $noti_stmt->bind_param("iss", $u_id, $title, $content);
                $noti_stmt->execute();
                $noti_stmt->close();
            }
        }

        // 2b. Đơn Tiền mặt (Khách chưa trả tiền): Tự động chuyển Đã hủy
        $conn->query("
            UPDATE dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
            SET ds.trang_thai = 'Đã hủy'
            WHERE ds.trang_thai = 'Chờ xác nhận'
              AND tt.phuong_thuc = 'tien_mat'
              AND ds.deleted_at IS NULL
              AND CONCAT(ds.ngay_dat, ' ', kg.gio_bat_dau) <= NOW()
        ");

        // 3. Đồng bộ giải phóng các slot khóa ở cấp CSDL cho các đơn đã hủy hoặc hoàn tiền
        $conn->query("
            UPDATE dat_san_slots dss
            JOIN dat_san ds ON dss.dat_san_id = ds.dat_san_id
            SET dss.trang_thai = 'released'
            WHERE ds.trang_thai IN ('Đã hủy', 'Hủy', 'Chờ hoàn tiền', 'Đã hoàn tiền')
              AND dss.trang_thai = 'active'
        ");

        // 4. Tự động đồng bộ điểm uy tín +5đ cho các trận đấu đã kết thúc
        require_once __DIR__ . '/../includes/db.php';
        if (function_exists('syncCompletedMatchesReputation')) {
            syncCompletedMatchesReputation($conn);
        }
    }
}
