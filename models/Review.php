<?php
namespace Models;

use Config\Database;

class Review {

    /**
     * Lấy danh sách đánh giá theo sân bóng
     */
    public static function getByPitchId($san_id, $limit = 10) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT dg.*, nd.ho_ten, nd.avatar, nd.ten_dang_nhap
            FROM danh_gia dg
            JOIN nguoi_dung nd ON dg.nguoi_dung_id = nd.nguoi_dung_id
            WHERE dg.san_id = ?
            ORDER BY dg.ngay_tao DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $san_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
        }
        $stmt->close();
        return $list;
    }

    /**
     * Lấy điểm đánh giá trung bình và tổng số lượt đánh giá của 1 sân
     */
    public static function getAverageRating($san_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT AVG(so_sao) as avg_rating, COUNT(*) as total_reviews FROM danh_gia WHERE san_id = ?");
        $stmt->bind_param("i", $san_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return [
            'avg_rating' => round((float)($res['avg_rating'] ?? 5.0), 1),
            'total_reviews' => (int)($res['total_reviews'] ?? 0)
        ];
    }

    /**
     * Thêm một đánh giá mới
     */
    public static function create($nguoi_dung_id, $san_id, $so_sao, $noi_dung, $dat_san_id = null) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO danh_gia (nguoi_dung_id, san_id, so_sao, noi_dung, dat_san_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisi", $nguoi_dung_id, $san_id, $so_sao, $noi_dung, $dat_san_id);
        $res = $stmt->execute();
        $insertId = $conn->insert_id;
        $stmt->close();
        return $res ? $insertId : false;
    }

    /**
     * Kiểm tra người dùng đã đánh giá đơn này chưa
     */
    public static function hasReviewed($nguoi_dung_id, $dat_san_id) {
        if (!$dat_san_id) return false;
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT danh_gia_id FROM danh_gia WHERE nguoi_dung_id = ? AND dat_san_id = ?");
        $stmt->bind_param("ii", $nguoi_dung_id, $dat_san_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = ($res->num_rows > 0);
        $stmt->close();
        return $exists;
    }
}

