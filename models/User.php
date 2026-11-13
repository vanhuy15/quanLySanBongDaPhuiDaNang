<?php
namespace Models;

use Config\Database;

class User {

    /**
     * Tìm người dùng theo ID
     */
    public static function findById($id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE nguoi_dung_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
     * Tìm người dùng theo tên đăng nhập
     */
    public static function findByUsername($username) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE ten_dang_nhap = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
     * Tìm người dùng theo email
     */
    public static function findByEmail($email) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
     * Tạo tài khoản người dùng mới
     */
    public static function create($username, $hashedPassword, $hoTen, $email, $sdt, $vaiTro = 'khach_hang') {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, email, sdt, vai_tro, diem_uy_tin, trang_thai) VALUES (?, ?, ?, ?, ?, ?, 100, 'active')");
        $stmt->bind_param("ssssss", $username, $hashedPassword, $hoTen, $email, $sdt, $vaiTro);
        $res = $stmt->execute();
        $insertId = $conn->insert_id;
        $stmt->close();
        return $res ? $insertId : false;
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public static function updateProfile($id, $hoTen, $sdt, $email, $avatar = null) {
        $conn = Database::getConnection();
        if ($avatar) {
            $stmt = $conn->prepare("UPDATE nguoi_dung SET ho_ten = ?, sdt = ?, email = ?, avatar = ? WHERE nguoi_dung_id = ?");
            $stmt->bind_param("ssssi", $hoTen, $sdt, $email, $avatar, $id);
        } else {
            $stmt = $conn->prepare("UPDATE nguoi_dung SET ho_ten = ?, sdt = ?, email = ? WHERE nguoi_dung_id = ?");
            $stmt->bind_param("sssi", $hoTen, $sdt, $email, $id);
        }
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Đổi mật khẩu
     */
    public static function updatePassword($id, $newHashedPassword) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE nguoi_dung_id = ?");
        $stmt->bind_param("si", $newHashedPassword, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Thay đổi điểm uy tín (cộng hoặc trừ) và ghi log lịch sử
     */
    public static function updateReputation($id, $delta, $actionType, $note = '') {
        $conn = Database::getConnection();
        
        // Cập nhật điểm uy tín (không vượt quá 100, không dưới 0)
        if ($delta >= 0) {
            $stmt = $conn->prepare("UPDATE nguoi_dung SET diem_uy_tin = LEAST(100, diem_uy_tin + ?) WHERE nguoi_dung_id = ?");
        } else {
            $absDelta = abs($delta);
            $stmt = $conn->prepare("UPDATE nguoi_dung SET diem_uy_tin = GREATEST(0, diem_uy_tin - ?) WHERE nguoi_dung_id = ?");
            $delta = -$absDelta;
        }
        $val = abs($delta);
        $stmt->bind_param("ii", $val, $id);
        $stmt->execute();
        $stmt->close();

        // Lấy điểm sau cùng
        $currentScore = (int)($conn->query("SELECT diem_uy_tin FROM nguoi_dung WHERE nguoi_dung_id = $id")->fetch_row()[0] ?? 100);

        // Ghi nhận vào bảng lịch sử
        $histStmt = $conn->prepare("INSERT INTO lich_su_uy_tin (nguoi_dung_id, so_diem, diem_sau_cung, loai_hanh_dong, ghi_chu) VALUES (?, ?, ?, ?, ?)");
        $histStmt->bind_param("iiiss", $id, $delta, $currentScore, $actionType, $note);
        $histStmt->execute();
        $histStmt->close();

        return $currentScore;
    }

    /**
     * Lấy lịch sử thay đổi điểm uy tín
     */
    public static function getReputationHistory($id, $limit = 20) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM lich_su_uy_tin WHERE nguoi_dung_id = ? ORDER BY ngay_tao DESC LIMIT ?");
        $stmt->bind_param("ii", $id, $limit);
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
     * Cập nhật trạng thái tài khoản (active, banned, inactive)
     */
    public static function updateStatus($id, $status) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE nguoi_dung SET trang_thai = ? WHERE nguoi_dung_id = ?");
        $stmt->bind_param("si", $status, $id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Đếm tổng số khách hàng
     */
    public static function countCustomers() {
        $conn = Database::getConnection();
        $res = $conn->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'khach_hang'");
        return (int)($res->fetch_row()[0] ?? 0);
    }
}

