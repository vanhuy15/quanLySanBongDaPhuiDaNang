<?php
session_start();
include '../../includes/db.php'; 
require_once '../../includes/send_mail.php';
require_once '../../includes/email_templates.php';

require_admin();

// Lấy box hiện tại để quay về
$status_return = isset($_GET['status_return']) ? $_GET['status_return'] : 'tat_ca';

$dat_san_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$trang_thai_moi = "";

if ($action == 'xacnhan' || $action == 'thutien_mat') {
    $trang_thai_moi = "Đã xác nhận";
} elseif ($action == 'hoantien_xong') {
    $trang_thai_moi = "Đã hoàn tiền";
} elseif ($action == 'huy' || $action == 'khach_bung_san') {
    $trang_thai_moi = "Đã hủy"; 
}

if ($dat_san_id > 0 && !empty($trang_thai_moi)) {
    if (class_exists('\\Models\\AuditLog')) {
        \Models\AuditLog::log($_SESSION['nguoi_dung_id'] ?? null, 'ADMIN_UPDATE_BOOKING', 'dat_san', $dat_san_id, "Hành động: {$action} | Trạng thái mới: {$trang_thai_moi}");
    }
    
    // Lấy thông tin đơn đặt sân và người dùng trước khi cập nhật
    $stmt_info = $conn->prepare("
        SELECT ds.*, nd.nguoi_dung_id, nd.ho_ten, nd.email, sb.ten_san, sb.dia_chi, kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien 
        FROM dat_san ds
        JOIN nguoi_dung nd ON ds.nguoi_dung_id = nd.nguoi_dung_id
        JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
        JOIN san_bong sb ON kg.san_id = sb.san_id
        WHERE ds.dat_san_id = ?
    ");
    $stmt_info->bind_param("i", $dat_san_id);
    $stmt_info->execute();
    $booking = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();

    // 1. Cập nhật bảng 'dat_san'
    $stmt = $conn->prepare("UPDATE dat_san SET trang_thai = ? WHERE dat_san_id = ?");
    $stmt->bind_param("si", $trang_thai_moi, $dat_san_id);
    $stmt->execute();
    $stmt->close();

    // Tự động giải phóng slot cấp CSDL nếu đơn chuyển sang trạng thái Hủy hoặc Hoàn tiền
    if (in_array($trang_thai_moi, ['Đã hủy', 'Hủy', 'Đã hoàn tiền'])) {
        $rel_stmt = $conn->prepare("UPDATE dat_san_slots SET trang_thai = 'released' WHERE dat_san_id = ?");
        if ($rel_stmt) {
            $rel_stmt->bind_param("i", $dat_san_id);
            $rel_stmt->execute();
            $rel_stmt->close();
        }
    }

    // 2. Xử lý các action cụ thể
    if ($action == 'thutien_mat' || $action == 'xacnhan') {
        $stmt_tt = $conn->prepare("UPDATE thanh_toan SET trang_thai = 'da_thanh_toan' WHERE dat_san_id = ?");
        $stmt_tt->bind_param("i", $dat_san_id);
        $stmt_tt->execute();
        $stmt_tt->close();

        if ($booking) {
            $user_id = $booking['nguoi_dung_id'];
            $ten_san = $booking['ten_san'];
            $san_con = $booking['ten_san_con'];
            $ngay_dat = date('d/m/Y', strtotime($booking['ngay_dat']));
            $gio_da = date('H:i', strtotime($booking['gio_bat_dau'])) . ' - ' . date('H:i', strtotime($booking['gio_ket_thuc']));
            $match_end_time = $booking['ngay_dat'] . ' ' . $booking['gio_ket_thuc'];

            // Chỉ cộng điểm khi trận đấu đã diễn ra xong (hết giờ đá)
            if (strtotime($match_end_time) <= time()) {
                $stmt_uy_tin = $conn->prepare("UPDATE nguoi_dung SET diem_uy_tin = LEAST(100, diem_uy_tin + 5) WHERE nguoi_dung_id = ?");
                $stmt_uy_tin->bind_param("i", $user_id);
                $stmt_uy_tin->execute();
                $stmt_uy_tin->close();

                $p_short = preg_replace('/^Sân bóng đá\s*/iu', '', $ten_san);
                $p_short = str_replace('Trường Đại Học Sư Phạm-Đà Nẵng', 'ĐH Sư Phạm', $p_short);

                $current_score = (int)($conn->query("SELECT diem_uy_tin FROM nguoi_dung WHERE nguoi_dung_id = $user_id")->fetch_row()[0] ?? 100);
                $stmt_log = $conn->prepare("INSERT INTO lich_su_uy_tin (nguoi_dung_id, so_diem, diem_sau_cung, loai_hanh_dong, ghi_chu, ngay_tao) VALUES (?, 5, ?, 'hoan_tat_dat_san', ?, ?)");
                $log_desc = "Hoàn thành đơn #$dat_san_id ($p_short)";
                $stmt_log->bind_param("iiss", $user_id, $current_score, $log_desc, $match_end_time);
                $stmt_log->execute();
                $stmt_log->close();
            }
            
            $tb_title = ($action == 'thutien_mat') ? "Đã xác nhận thu tiền mặt #$dat_san_id" : "Đơn đặt sân #$dat_san_id đã được xác nhận!";
            $tb_content = "Đơn đặt sân $ten_san (Sân $san_con) ngày $ngay_dat ($gio_da) đã được hoàn tất thanh toán thành công.";
            $tb_link = "index.php?controller=User&action=history";
            
            $stmt_tb = $conn->prepare("INSERT INTO thong_bao (nguoi_dung_id, tieu_de, noi_dung, loai, lien_ket) VALUES (?, ?, ?, 'xac_nhan', ?)");
            $stmt_tb->bind_param("isss", $user_id, $tb_title, $tb_content, $tb_link);
            $stmt_tb->execute();
            $stmt_tb->close();
        }

    } elseif ($action == 'khach_bung_san') {
        // CẬP NHẬT TRỪ ĐIỂM UY TÍN & TĂNG SỐ LẦN BÙNG SÂN
        $stmt_tt = $conn->prepare("UPDATE thanh_toan SET trang_thai = 'da_huy' WHERE dat_san_id = ?");
        $stmt_tt->bind_param("i", $dat_san_id);
        $stmt_tt->execute();
        $stmt_tt->close();

        if ($booking) {
            $user_id = $booking['nguoi_dung_id'];
            $ten_san = $booking['ten_san'];
            $ngay_dat = date('d/m/Y', strtotime($booking['ngay_dat']));
            $gio_da = date('H:i', strtotime($booking['gio_bat_dau'])) . ' - ' . date('H:i', strtotime($booking['gio_ket_thuc']));

            // Fix SQL Injection: Phạt tài khoản — trừ điểm uy tín và tăng số lần bùng sân
            $stmt_phat = $conn->prepare("UPDATE nguoi_dung SET so_lan_bung_san = so_lan_bung_san + 1, diem_uy_tin = GREATEST(0, diem_uy_tin - 25) WHERE nguoi_dung_id = ?");
            $stmt_phat->bind_param("i", $user_id);
            $stmt_phat->execute();
            $stmt_phat->close();

            $p_short = preg_replace('/^Sân bóng đá\s*/iu', '', $ten_san);
            $p_short = str_replace('Trường Đại Học Sư Phạm-Đà Nẵng', 'ĐH Sư Phạm', $p_short);

            $current_score = (int)($conn->query("SELECT diem_uy_tin FROM nguoi_dung WHERE nguoi_dung_id = $user_id")->fetch_row()[0] ?? 0);
            $stmt_log = $conn->prepare("INSERT INTO lich_su_uy_tin (nguoi_dung_id, so_diem, diem_sau_cung, loai_hanh_dong, ghi_chu) VALUES (?, -25, ?, 'bung_san', ?)");
            $log_desc = "Bùng sân đơn #$dat_san_id ($p_short)";
            $stmt_log->bind_param("iis", $user_id, $current_score, $log_desc);
            $stmt_log->execute();
            $stmt_log->close();

            // Thông báo cảnh cáo cho khách
            $tb_title = "CẢNH BÁO UY TÍN: Ghi nhận bùng sân đơn #$dat_san_id";
            $tb_content = "Bạn bị quản trị viên ghi nhận KHÔNG ĐẾN SÂN cho lịch đặt $ten_san ngày $ngay_dat ($gio_da). Điểm uy tín của bạn đã bị trừ!";
            $tb_link = "index.php?controller=User&action=history";
            
            $stmt_tb = $conn->prepare("INSERT INTO thong_bao (nguoi_dung_id, tieu_de, noi_dung, loai, lien_ket) VALUES (?, ?, ?, 'huy_san', ?)");
            $stmt_tb->bind_param("isss", $user_id, $tb_title, $tb_content, $tb_link);
            $stmt_tb->execute();
            $stmt_tb->close();
        }

    } elseif ($action == 'hoantien_xong') {
        $stmt_tt = $conn->prepare("UPDATE thanh_toan SET trang_thai = 'da_huy' WHERE dat_san_id = ?");
        $stmt_tt->bind_param("i", $dat_san_id);
        $stmt_tt->execute();
        $stmt_tt->close();

        if ($booking) {
            $user_id = $booking['nguoi_dung_id'];
            $ten_san = $booking['ten_san'];
            
            $tb_title = "Hoàn tiền thành công cho đơn #$dat_san_id";
            $tb_content = "Quản trị viên đã chuyển tiền hoàn lại vào tài khoản ngân hàng của bạn cho đơn đặt sân $ten_san.";
            $tb_link = "index.php?controller=User&action=history";
            
            $stmt_tb = $conn->prepare("INSERT INTO thong_bao (nguoi_dung_id, tieu_de, noi_dung, loai, lien_ket) VALUES (?, ?, ?, 'xac_nhan', ?)");
            $stmt_tb->bind_param("isss", $user_id, $tb_title, $tb_content, $tb_link);
            $stmt_tb->execute();
            $stmt_tb->close();
        }

    } else {
        // Hủy đơn thông thường
        $stmt_tt = $conn->prepare("UPDATE thanh_toan SET trang_thai = 'da_huy' WHERE dat_san_id = ?");
        $stmt_tt->bind_param("i", $dat_san_id);
        $stmt_tt->execute();
        $stmt_tt->close();

        if ($booking) {
            $user_id = $booking['nguoi_dung_id'];
            $ten_san = $booking['ten_san'];
            $ngay_dat = date('d/m/Y', strtotime($booking['ngay_dat']));
            
            $tb_title = "Đơn đặt sân #$dat_san_id đã bị hủy";
            $tb_content = "Đơn đặt sân $ten_san vào ngày $ngay_dat đã bị hủy.";
            $tb_link = "index.php?controller=User&action=history";
            
            $stmt_tb = $conn->prepare("INSERT INTO thong_bao (nguoi_dung_id, tieu_de, noi_dung, loai, lien_ket) VALUES (?, ?, ?, 'huy_san', ?)");
            $stmt_tb->bind_param("isss", $user_id, $tb_title, $tb_content, $tb_link);
            $stmt_tb->execute();
            $stmt_tb->close();
        }
    }
}

// Chuyển hướng về trang danh sách
if (!empty($status_return) && $status_return != 'tat_ca') {
    header("Location: index.php?status=" . urlencode($status_return));
} else {
    header("Location: index.php");
}
exit();