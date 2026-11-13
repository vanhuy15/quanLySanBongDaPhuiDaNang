<?php
namespace Models;

use Config\Database;

class DoanhThu {

    /**
     * Lấy tổng doanh thu toàn thời gian (tính chuẩn cho cả sân 5 và sân 7)
     */
    public static function getTotalRevenue() {
        $conn = Database::getConnection();
        $sql = "
            SELECT COALESCE(SUM(
                CASE WHEN ds.loai_san_dat = 'san_7' AND kg.gia_tien_san_7 IS NOT NULL AND kg.gia_tien_san_7 > 0
                     THEN kg.gia_tien_san_7
                     ELSE kg.gia_tien
                END
            ), 0) as total
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            WHERE ds.trang_thai = 'Đã xác nhận' AND ds.deleted_at IS NULL
        ";
        $res = $conn->query($sql);
        return (float)($res->fetch_assoc()['total'] ?? 0);
    }

    /**
     * Lấy doanh thu 7 ngày gần nhất phục vụ vẽ biểu đồ Dashboard
     */
    public static function getRevenueLast7Days() {
        $conn = Database::getConnection();
        $labels = [];
        $data_chart = [];

        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(
                CASE WHEN ds.loai_san_dat = 'san_7' AND kg.gia_tien_san_7 IS NOT NULL AND kg.gia_tien_san_7 > 0
                     THEN kg.gia_tien_san_7
                     ELSE kg.gia_tien
                END
            ), 0) as total
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            WHERE ds.trang_thai = 'Đã xác nhận'
              AND ds.ngay_dat = ?
              AND ds.deleted_at IS NULL
        ");

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d/m', strtotime($date));
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $data_chart[] = (float)($row['total'] ?? 0);
        }
        $stmt->close();

        return [
            'labels' => $labels,
            'data_chart' => $data_chart
        ];
    }
}

