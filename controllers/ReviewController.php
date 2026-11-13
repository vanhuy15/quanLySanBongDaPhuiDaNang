<?php
namespace Controllers;

use Core\Controller;
use Config\Database;
use Models\User;
use Models\Review;

class ReviewController extends Controller {
    
    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['nguoi_dung_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá.']);
        }

        $nguoi_dung_id = $_SESSION['nguoi_dung_id'];
        $dat_san_id = intval($_POST['dat_san_id'] ?? 0);
        $san_id = intval($_POST['san_id'] ?? 0);
        $so_sao = max(1, min(5, intval($_POST['so_sao'] ?? 5)));
        $noi_dung = trim($_POST['noi_dung'] ?? '');

        if ($san_id <= 0 || $dat_san_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        }

        $conn = Database::getConnection();

        // 1. Kiểm tra đơn đặt sân có đúng của người này và trạng thái Đã xác nhận không
        $stmt_check = $conn->prepare("
            SELECT ds.dat_san_id, ds.ngay_dat, kg.san_id, kg.gio_ket_thuc 
            FROM dat_san ds 
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id 
            WHERE ds.dat_san_id = ? AND ds.nguoi_dung_id = ? AND ds.trang_thai = 'Đã xác nhận'
        ");
        $stmt_check->bind_param("ii", $dat_san_id, $nguoi_dung_id);
        $stmt_check->execute();
        $booking = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$booking) {
            $this->jsonResponse(['success' => false, 'message' => 'Đơn đặt sân chưa được duyệt hoặc không hợp lệ để đánh giá.']);
        }

        // Kiểm tra xem trận đấu đã diễn ra xong chưa (sau giờ kết thúc)
        $match_end_time = strtotime($booking['ngay_dat'] . ' ' . $booking['gio_ket_thuc']);
        if ($match_end_time > time()) {
            $this->jsonResponse(['success' => false, 'message' => 'Trận đấu chưa kết thúc! Bạn chỉ có thể đánh giá sau khi hoàn thành thời gian đá trên sân.']);
        }

        $actual_san_id = $booking['san_id'];

        // 2. Kiểm tra xem đã đánh giá chưa (nếu đã có thì update, chưa có thì insert)
        $stmt_exist = $conn->prepare("SELECT danh_gia_id FROM danh_gia WHERE dat_san_id = ?");
        $stmt_exist->bind_param("i", $dat_san_id);
        $stmt_exist->execute();
        $exist = $stmt_exist->get_result()->fetch_assoc();
        $stmt_exist->close();

        if ($exist) {
            $danh_gia_id = $exist['danh_gia_id'];
            $stmt_update = $conn->prepare("UPDATE danh_gia SET so_sao = ?, noi_dung = ?, ngay_tao = NOW() WHERE danh_gia_id = ?");
            $stmt_update->bind_param("isi", $so_sao, $noi_dung, $danh_gia_id);
            if ($stmt_update->execute()) {
                $stmt_update->close();
                $this->jsonResponse(['success' => true, 'message' => 'Cập nhật đánh giá thành công! Cảm ơn bạn.']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Lỗi khi cập nhật đánh giá.']);
            }
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO danh_gia (san_id, nguoi_dung_id, dat_san_id, so_sao, noi_dung) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("iiiis", $actual_san_id, $nguoi_dung_id, $dat_san_id, $so_sao, $noi_dung);
            if ($stmt_insert->execute()) {
                $stmt_insert->close();

                // Cộng 2 điểm uy tín và ghi log lịch sử qua Model
                User::updateReputation($nguoi_dung_id, 2, 'danh_gia', 'Viết đánh giá trải nghiệm sân bóng');

                $this->jsonResponse(['success' => true, 'message' => 'Đánh giá của bạn đã được ghi nhận! Bạn được cộng +2 điểm uy tín.']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Lỗi khi gửi đánh giá.']);
            }
        }
    }
}
