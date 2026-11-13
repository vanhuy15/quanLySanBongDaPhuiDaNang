<?php
namespace Controllers;

use Core\Controller;
use Config\Database;

require_once __DIR__ . '/../includes/send_mail.php';
require_once __DIR__ . '/../includes/email_templates.php';

class PaymentController extends Controller {

    public function index() {
        if (!isset($_SESSION['nguoi_dung_id'])) {
            $this->redirect('index.php?controller=Auth&action=login');
        }

        $dat_san_id = isset($_GET['dat_san_id']) ? intval($_GET['dat_san_id']) : 0;
        $nguoi_dung_id = $_SESSION['nguoi_dung_id'];
        
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT ds.*, sb.san_id, sb.ten_san, sb.dia_chi, kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien, kg.gia_tien_san_7, nd.ho_ten, nd.email, nd.sdt, nd.diem_uy_tin, nd.so_lan_bung_san 
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            JOIN san_bong sb ON kg.san_id = sb.san_id
            JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
            WHERE ds.dat_san_id=? AND ds.nguoi_dung_id=?
        ");
        $stmt->bind_param("ii", $dat_san_id, $nguoi_dung_id);
        $stmt->execute();
        $dat_san = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($dat_san && isset($dat_san['loai_san_dat']) && $dat_san['loai_san_dat'] === 'san_7' && !empty($dat_san['gia_tien_san_7'])) {
            $dat_san['gia_tien'] = $dat_san['gia_tien_san_7'];
        }
        
        if(!$dat_san){
            $_SESSION['flash_error'] = "Đơn đặt sân không tồn tại hoặc không hợp lệ.";
            $this->redirect('index.php');
        }

        $user_diem_uy_tin = (int)($dat_san['diem_uy_tin'] ?? 100);

        $stmt_check = $conn->prepare("SELECT * FROM thanh_toan WHERE dat_san_id = ?");
        $stmt_check->bind_param("i", $dat_san_id);
        $stmt_check->execute();
        $existing_payment = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        // Tự động kiểm tra thời hạn 10 phút nếu chưa chọn thanh toán
        $created_time = strtotime($dat_san['ngay_tao']);
        $expire_time = $created_time + (10 * 60);
        $remaining_seconds = $expire_time - time();

        if ($remaining_seconds <= 0 && $dat_san['trang_thai'] === 'Chờ thanh toán' && !$existing_payment) {
            $stmt_cancel = $conn->prepare("UPDATE dat_san SET trang_thai = 'Đã hủy' WHERE dat_san_id = ?");
            $stmt_cancel->bind_param("i", $dat_san_id);
            $stmt_cancel->execute();
            $stmt_cancel->close();
            $dat_san['trang_thai'] = 'Đã hủy';
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$existing_payment) {
            if ($dat_san['trang_thai'] === 'Đã hủy') {
                $rebook_param = !empty($dat_san['san_id']) ? '&san_id=' . (int)$dat_san['san_id'] : '';
                $_SESSION['flash_error'] = 'Đơn đặt sân đã hết hạn giữ chỗ (10 phút)! Vui lòng chọn và đặt lại sân.';
                $this->redirect('index.php?controller=DatSan&action=book' . $rebook_param);
            }

            $phuong_thuc = $_POST['phuong_thuc'] ?? 'tien_mat';
            
            // Khóa tiền mặt nếu điểm uy tín < 60
            if ($phuong_thuc === 'tien_mat' && $user_diem_uy_tin < 60) {
                $this->render('payment/index', [
                    'dat_san' => $dat_san,
                    'dat_san_id' => $dat_san_id,
                    'existing_payment' => $existing_payment,
                    'remaining_seconds' => max(0, $remaining_seconds),
                    'error' => "⚠️ Điểm uy tín của bạn hiện tại là {$user_diem_uy_tin}/100 (Dưới 60đ). Bạn bắt buộc phải chọn Chuyển khoản VietQR!"
                ]);
                return;
            }

            if ($phuong_thuc === 'chuyen_khoan') {
                // CHUYỂN KHOẢN: Chờ admin kiểm tra tài khoản ngân hàng và duyệt
                $tt_trang_thai = 'cho_xu_ly';
                $ds_trang_thai = 'Chờ xác nhận';
            } else {
                // TIỀN MẶT: Chờ admin gọi điện xác nhận hoặc duyệt thủ công
                $tt_trang_thai = 'cho_xu_ly';
                $ds_trang_thai = 'Chờ xác nhận';
            }

            // Ghi nhận bảng thanh_toan
            $stmt2 = $conn->prepare("INSERT INTO thanh_toan (dat_san_id, phuong_thuc, trang_thai) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $dat_san_id, $phuong_thuc, $tt_trang_thai);
            $stmt2->execute();
            $stmt2->close();

            // Cập nhật trạng thái dat_san thành Chờ xác nhận (Vẫn giữ chỗ, admin sẽ duyệt)
            $stmt_update_ds = $conn->prepare("UPDATE dat_san SET trang_thai = ? WHERE dat_san_id = ?");
            $stmt_update_ds->bind_param("si", $ds_trang_thai, $dat_san_id);
            $stmt_update_ds->execute();
            $stmt_update_ds->close();

            $existing_payment = ['phuong_thuc' => $phuong_thuc, 'trang_thai' => $tt_trang_thai];
            $dat_san['trang_thai'] = $ds_trang_thai;

            // Tạo thông báo
            $ngay_dat = date('d/m/Y', strtotime($dat_san['ngay_dat']));
            $gio_da = date('H:i', strtotime($dat_san['gio_bat_dau'])) . ' - ' . date('H:i', strtotime($dat_san['gio_ket_thuc']));
            $ten_san = $dat_san['ten_san'];
            $san_con = $dat_san['ten_san_con'];

            if ($phuong_thuc === 'chuyen_khoan') {
                $tb_title = "Yêu cầu thanh toán VietQR đơn #$dat_san_id";
                $tb_content = "Yêu cầu thanh toán chuyển khoản đơn $ten_san (Sân $san_con) ngày $ngay_dat lúc $gio_da đã được tiếp nhận. Đơn đang chờ Admin đối soát ngân hàng và xác nhận duyệt.";
            } else {
                $tb_title = "Giữ chỗ sân thành công #$dat_san_id";
                $tb_content = "Đơn đặt sân $ten_san (Sân $san_con) ngày $ngay_dat lúc $gio_da đã được giữ chỗ. Vui lòng thanh toán tiền mặt sau khi hoàn thành trận đấu.";
            }

            \Models\Notification::create(
                $nguoi_dung_id,
                $tb_title,
                $tb_content,
                'dat_san',
                "index.php?controller=User&action=history"
            );

            // Gửi email hóa đơn và gửi cho Admin
            try {
                if (!empty($dat_san['email'])) {
                    $emailSubject = "Xác nhận đặt sân thành công #" . $dat_san_id . " - Sân " . $ten_san;
                    $emailBody = getBookingEmailHtml($dat_san, 'confirmed');
                    sendEmail($dat_san['email'], $emailSubject, $emailBody);
                }

                $adminEmail = getenv('MAIL_ADMIN_NOTIFY') ?: 'alexanderhuy2@gmail.com';
                $adminSubject = "[ĐƠN ĐẶT SÂN MỚI #" . $dat_san_id . "] " . $dat_san['ho_ten'] . " - Sân " . $ten_san;
                $adminBody = getAdminNewBookingEmailHtml($dat_san);
                sendEmail($adminEmail, $adminSubject, $adminBody);
            } catch (\Throwable $mailEx) {
                error_log("Mail error in PaymentController: " . $mailEx->getMessage());
            }
        }
        
        $this->render('payment/index', [
            'dat_san' => $dat_san,
            'dat_san_id' => $dat_san_id,
            'existing_payment' => $existing_payment,
            'remaining_seconds' => max(0, $remaining_seconds)
        ]);
    }
}
