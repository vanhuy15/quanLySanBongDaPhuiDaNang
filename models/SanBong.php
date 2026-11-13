<?php
namespace Models;

use Config\Database;

class SanBong {

    /**
     * Lấy toàn bộ danh sách sân bóng
     */
    public static function getAll() {
        $conn = Database::getConnection();
        $res = $conn->query("SELECT * FROM san_bong ORDER BY ten_san ASC");
        $list = [];
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
        }
        return $list;
    }

    /**
     * Lấy thông tin sân bóng theo ID
     */
    public static function findById($id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM san_bong WHERE san_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
     * Lấy danh sách sân kèm giá thấp nhất để hiển thị trang chủ
     */
    public static function getPitchesWithMinPrice() {
        $conn = Database::getConnection();
        $sql = "SELECT sb.*, MIN(kg.gia_tien) AS gia_thap_nhat 
                FROM san_bong sb 
                LEFT JOIN khung_gio kg ON sb.san_id = kg.san_id 
                GROUP BY sb.san_id 
                ORDER BY sb.ten_san ASC";
        $res = $conn->query($sql);
        $list = [];
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
        }
        return $list;
    }

    /**
     * Đếm tổng số sân bóng
     */
    public static function countAll() {
        $conn = Database::getConnection();
        $res = $conn->query("SELECT COUNT(*) FROM san_bong");
        return (int)($res->fetch_row()[0] ?? 0);
    }

    /**
     * Thêm sân bóng mới
     */
    public static function create($ten_san, $dia_chi, $so_luong, $loai_san, $quy_tac_ghep, $gia_san_7 = null, $latitude = null, $longitude = null) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO san_bong (ten_san, dia_chi, so_luong, loai_san, quy_tac_ghep, gia_san_7, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisidss", $ten_san, $dia_chi, $so_luong, $loai_san, $quy_tac_ghep, $gia_san_7, $latitude, $longitude);
        $res = $stmt->execute();
        $insertId = $conn->insert_id;
        $stmt->close();
        return $res ? $insertId : false;
    }

    /**
     * Cập nhật thông tin sân bóng
     */
    public static function update($san_id, $ten_san, $dia_chi, $so_luong, $loai_san, $quy_tac_ghep, $gia_san_7 = null, $latitude = null, $longitude = null) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE san_bong SET ten_san = ?, dia_chi = ?, so_luong = ?, loai_san = ?, quy_tac_ghep = ?, gia_san_7 = ?, latitude = ?, longitude = ? WHERE san_id = ?");
        $stmt->bind_param("ssisidssi", $ten_san, $dia_chi, $so_luong, $loai_san, $quy_tac_ghep, $gia_san_7, $latitude, $longitude, $san_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Xóa sân bóng
     */
    public static function delete($san_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM san_bong WHERE san_id = ?");
        $stmt->bind_param("i", $san_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
}

