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
     * Tối ưu hóa: 1 truy vấn duy nhất thay vì lặp 7 truy vấn mạng
     */
    public static function getRevenueLast7Days() {
        $conn = Database::getConnection();
        $labels = [];
        $data_chart = [];

        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate = date('Y-m-d');

        $stmt = $conn->prepare("
            SELECT DATE(ds.ngay_dat) as d,
                   COALESCE(SUM(
                       CASE WHEN ds.loai_san_dat = 'san_7' AND kg.gia_tien_san_7 IS NOT NULL AND kg.gia_tien_san_7 > 0
                            THEN kg.gia_tien_san_7
                            ELSE kg.gia_tien
                       END
                   ), 0) as total
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            WHERE ds.trang_thai = 'Đã xác nhận'
              AND ds.ngay_dat BETWEEN ? AND ?
              AND ds.deleted_at IS NULL
            GROUP BY DATE(ds.ngay_dat)
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $res = $stmt->get_result();
        $revenueMap = [];
        while ($row = $res->fetch_assoc()) {
            $revenueMap[$row['d']] = (float)$row['total'];
        }
        $stmt->close();

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d/m', strtotime($date));
            $data_chart[] = $revenueMap[$date] ?? 0.0;
        }

        return [
            'labels' => $labels,
            'data_chart' => $data_chart
        ];
    }
}

