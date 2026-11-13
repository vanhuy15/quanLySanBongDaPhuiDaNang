<?php
namespace Models;

use Config\Database;

class KhungGio {

    /**
     * Lấy các khung giờ theo sân
     */
    public static function getByPitchId($san_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM khung_gio WHERE san_id = ? ORDER BY gio_bat_dau ASC");
        $stmt->bind_param("i", $san_id);
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
     * Lấy chi tiết khung giờ theo ID
     */
    public static function findById($id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT kg.*, sb.ten_san FROM khung_gio kg JOIN san_bong sb ON kg.san_id = sb.san_id WHERE kg.khung_gio_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
     * Lấy tất cả khung giờ có kèm thông tin sân (hỗ trợ lọc theo sân)
     */
    public static function getAllWithPitch($san_id_filter = 0) {
        $conn = Database::getConnection();
        if ($san_id_filter > 0) {
            $stmt = $conn->prepare("SELECT kg.*, sb.ten_san, sb.loai_san FROM khung_gio kg JOIN san_bong sb ON kg.san_id = sb.san_id WHERE kg.san_id = ? ORDER BY sb.ten_san ASC, kg.gio_bat_dau ASC");
            $stmt->bind_param("i", $san_id_filter);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $conn->query("SELECT kg.*, sb.ten_san, sb.loai_san FROM khung_gio kg JOIN san_bong sb ON kg.san_id = sb.san_id ORDER BY sb.ten_san ASC, kg.gio_bat_dau ASC");
        }
        $list = [];
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
        }
        if (isset($stmt)) $stmt->close();
        return $list;
    }

    /**
     * Tạo mới khung giờ
     */
    public static function create($san_id, $gio_bat_dau, $gio_ket_thuc, $gia_tien, $gia_tien_san_7 = null) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO khung_gio (san_id, gio_bat_dau, gio_ket_thuc, gia_tien, gia_tien_san_7) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdd", $san_id, $gio_bat_dau, $gio_ket_thuc, $gia_tien, $gia_tien_san_7);
        $res = $stmt->execute();
        $insertId = $conn->insert_id;
        $stmt->close();
        return $res ? $insertId : false;
    }

    /**
     * Cập nhật khung giờ
     */
    public static function update($khung_gio_id, $san_id, $gio_bat_dau, $gio_ket_thuc, $gia_tien, $gia_tien_san_7 = null) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE khung_gio SET san_id = ?, gio_bat_dau = ?, gio_ket_thuc = ?, gia_tien = ?, gia_tien_san_7 = ? WHERE khung_gio_id = ?");
        $stmt->bind_param("issddi", $san_id, $gio_bat_dau, $gio_ket_thuc, $gia_tien, $gia_tien_san_7, $khung_gio_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Xóa khung giờ
     */
    public static function delete($khung_gio_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM khung_gio WHERE khung_gio_id = ?");
        $stmt->bind_param("i", $khung_gio_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
}

