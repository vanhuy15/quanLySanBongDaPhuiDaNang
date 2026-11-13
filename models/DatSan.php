<?php
namespace Models;

use Config\Database;

class DatSan {

    /**
     * Dọn dẹp các đơn đặt sân quá hạn thanh toán
     */
    public static function cleanupExpiredBookings() {
        Database::cleanupExpiredBookings();
    }

    /**
     * Lấy danh sách các sân con đã bị khóa trong khung giờ và ngày cụ thể
     */
    public static function getLockedSubPitches($khung_gio_id, $ngay_dat) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT ten_san_con, san_con_khoa 
            FROM dat_san 
            WHERE khung_gio_id = ? AND ngay_dat = ? 
              AND (
                  trang_thai = 'Đã xác nhận' 
                  OR trang_thai = 'Chờ xác nhận' 
                  OR (trang_thai = 'Chờ thanh toán' AND TIMESTAMPDIFF(MINUTE, ngay_tao, NOW()) < 10)
              ) 
              AND deleted_at IS NULL
        ");
        $stmt->bind_param("is", $khung_gio_id, $ngay_dat);
        $stmt->execute();
        $res = $stmt->get_result();

        $locked = [];
        while ($r = $res->fetch_assoc()) {
            if (!empty($r['san_con_khoa'])) {
                $parts = explode(',', $r['san_con_khoa']);
                foreach ($parts as $p) {
                    if (trim($p) !== '') $locked[] = trim($p);
                }
            }
            if (!empty($r['ten_san_con'])) {
                $locked[] = trim($r['ten_san_con']);
            }
        }
        $stmt->close();
        return array_unique($locked);
    }

    /**
     * Thực hiện đặt sân có cơ chế khóa dòng chống Race Condition
     * @return array ['success' => bool, 'message' => string, 'dat_san_id' => int|null]
     */
    public static function bookWithLock($nguoi_dung_id, $khung_gio_id, $ngay_dat, $loai_san_dat, $ten_san_con, $san_con_khoa) {
        $conn = Database::getConnection();

        // 1. Kiểm tra điểm uy tín khách hàng
        $user = User::findById($nguoi_dung_id);
        $diem_uy_tin = (int)($user['diem_uy_tin'] ?? 100);

        if ($diem_uy_tin < 40) {
            return [
                'success' => false,
                'message' => "⛔ Quyền đặt sân bị khóa! Điểm uy tín của bạn hiện tại là {$diem_uy_tin}/100 (Dưới 40đ do bùng sân). Vui lòng liên hệ Hotline quản lý sân bóng để được hỗ trợ mở khóa."
            ];
        }

        // 2. Bắt đầu giao tác (Transaction)
        $conn->begin_transaction();

        try {
            $keys_to_check = explode(',', $san_con_khoa);
            $conflict_found = false;

            // FOR UPDATE: Khóa dòng ngăn chặn các transaction khác đọc/ghi cùng lúc
            $check_stmt = $conn->prepare("
                SELECT dat_san_id, ten_san_con, san_con_khoa 
                FROM dat_san 
                WHERE khung_gio_id = ? AND ngay_dat = ? 
                  AND (
                      trang_thai = 'Đã xác nhận' 
                      OR trang_thai = 'Chờ xác nhận' 
                      OR (trang_thai = 'Chờ thanh toán' AND TIMESTAMPDIFF(MINUTE, ngay_tao, NOW()) < 10)
                  ) 
                  AND deleted_at IS NULL 
                FOR UPDATE
            ");
            $check_stmt->bind_param("is", $khung_gio_id, $ngay_dat);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();

            while ($active_booking = $check_res->fetch_assoc()) {
                $already_locked = [];
                if (!empty($active_booking['san_con_khoa'])) {
                    $parts = explode(',', $active_booking['san_con_khoa']);
                    foreach ($parts as $p) $already_locked[] = trim($p);
                }
                if (!empty($active_booking['ten_san_con'])) {
                    $already_locked[] = trim($active_booking['ten_san_con']);
                }

                if (count(array_intersect($keys_to_check, $already_locked)) > 0) {
                    $conflict_found = true;
                    break;
                }
            }
            $check_stmt->close();

            if ($conflict_found) {
                $conn->rollback();
                return [
                    'success' => false,
                    'message' => "Rất tiếc! Sân bạn vừa chọn (hoặc sân con thành phần) vừa có người khác đặt. Vui lòng chọn sân hoặc khung giờ khác."
                ];
            }

            // Ghi nhận đặt sân
            $stmt_insert = $conn->prepare("
                INSERT INTO dat_san (nguoi_dung_id, khung_gio_id, ngay_dat, trang_thai, ten_san_con, loai_san_dat, san_con_khoa) 
                VALUES (?, ?, ?, 'Chờ thanh toán', ?, ?, ?)
            ");
            $stmt_insert->bind_param("iissss", $nguoi_dung_id, $khung_gio_id, $ngay_dat, $ten_san_con, $loai_san_dat, $san_con_khoa);
            $stmt_insert->execute();
            $new_dat_san_id = $conn->insert_id;
            $stmt_insert->close();

            // Commit giao dịch thành công
            $conn->commit();

            // Lấy thông tin chi tiết để gửi thông báo
            $stmt_detail = $conn->prepare("
                SELECT ds.*, nd.ho_ten, nd.email, sb.ten_san, sb.dia_chi, kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien, kg.gia_tien_san_7 
                FROM dat_san ds
                JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
                JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
                JOIN san_bong sb ON kg.san_id = sb.san_id
                WHERE ds.dat_san_id = ?
            ");
            $stmt_detail->bind_param("i", $new_dat_san_id);
            $stmt_detail->execute();
            $booking = $stmt_detail->get_result()->fetch_assoc();
            $stmt_detail->close();

            if ($booking) {
                $ten_san = $booking['ten_san'];
                $san_con = $booking['ten_san_con'];
                $ngay_dat_fmt = date('d/m/Y', strtotime($booking['ngay_dat']));
                $gio_da = date('H:i', strtotime($booking['gio_bat_dau'])) . ' - ' . date('H:i', strtotime($booking['gio_ket_thuc']));
                
                \Models\Notification::create(
                    $nguoi_dung_id,
                    "Vui lòng thanh toán đơn đặt sân #$new_dat_san_id",
                    "Bạn vừa giữ chỗ sân $ten_san ($san_con) ngày $ngay_dat_fmt lúc $gio_da. Vui lòng hoàn tất thanh toán trong 10 phút, nếu không đơn sẽ bị tự động hủy!",
                    'dat_san',
                    "index.php?controller=Payment&action=index&dat_san_id=$new_dat_san_id"
                );
            }

            return [
                'success' => true,
                'message' => 'Đặt sân thành công!',
                'dat_san_id' => $new_dat_san_id,
                'redirect' => "index.php?controller=Payment&action=index&dat_san_id=$new_dat_san_id"
            ];

        } catch (\Exception $e) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra trong quá trình giữ sân: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin đơn đặt sân theo ID kèm chi tiết sân và khung giờ
     */
    public static function findById($id) {
        $conn = Database::getConnection();
        $sql = "SELECT ds.*, sb.san_id, sb.ten_san, sb.dia_chi, sb.quy_tac_ghep,
                       kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien, kg.gia_tien_san_7,
                       nd.ho_ten, nd.email, nd.sdt, nd.diem_uy_tin, nd.so_lan_bung_san,
                       tt.phuong_thuc, tt.trang_thai as tt_trang_thai
                FROM dat_san ds
                JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
                JOIN san_bong sb ON kg.san_id = sb.san_id
                JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
                LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
                WHERE ds.dat_san_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    /**
     * Lấy lịch sử đặt sân của một người dùng
     */
    public static function getByUserId($user_id, $status_filter = null) {
        $conn = Database::getConnection();
        $where = "WHERE ds.nguoi_dung_id = ? AND ds.deleted_at IS NULL";
        if (!empty($status_filter)) {
            $where .= " AND ds.trang_thai = ?";
        }

        $sql = "SELECT ds.*, sb.san_id, sb.ten_san, sb.dia_chi, kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien, kg.gia_tien_san_7,
                       tt.phuong_thuc, tt.trang_thai as tt_trang_thai
                FROM dat_san ds
                JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
                JOIN san_bong sb ON kg.san_id = sb.san_id
                LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
                $where
                ORDER BY ds.ngay_dat DESC, kg.gio_bat_dau DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($status_filter)) {
            $stmt->bind_param("is", $user_id, $status_filter);
        } else {
            $stmt->bind_param("i", $user_id);
        }
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
     * Cập nhật trạng thái đơn đặt sân
     */
    public static function updateStatus($dat_san_id, $trang_thai) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE dat_san SET trang_thai = ? WHERE dat_san_id = ?");
        $stmt->bind_param("si", $trang_thai, $dat_san_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    /**
     * Xóa vĩnh viễn đơn đặt sân và bản ghi thanh toán liên quan
     */
    public static function deleteHard($dat_san_id) {
        $conn = Database::getConnection();
        $stmt1 = $conn->prepare("DELETE FROM thanh_toan WHERE dat_san_id = ?");
        $stmt1->bind_param("i", $dat_san_id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("DELETE FROM dat_san WHERE dat_san_id = ?");
        $stmt2->bind_param("i", $dat_san_id);
        $res = $stmt2->execute();
        $stmt2->close();
        return $res;
    }

    /**
     * Đếm số đơn chờ xác nhận
     */
    public static function countPending() {
        $conn = Database::getConnection();
        $res = $conn->query("SELECT COUNT(*) FROM dat_san WHERE trang_thai = 'Chờ xác nhận' AND deleted_at IS NULL");
        return (int)($res->fetch_row()[0] ?? 0);
    }

    /**
     * Đếm tổng số đơn
     */
    public static function countTotal() {
        $conn = Database::getConnection();
        $res = $conn->query("SELECT COUNT(*) FROM dat_san WHERE deleted_at IS NULL");
        return (int)($res->fetch_row()[0] ?? 0);
    }
}
