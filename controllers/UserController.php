<?php
namespace Controllers;

use Core\Controller;
use Config\Database;
use Models\User;
use Models\DatSan;

class UserController extends Controller {
    
    public function profile() {
        if (!isset($_SESSION['nguoi_dung_id']) || ($_SESSION['vai_tro'] ?? '') !== 'khach_hang') {
            $this->redirect('index.php?controller=Auth&action=login');
        }
        
        $nguoi_dung_id = $_SESSION['nguoi_dung_id'];
        $conn = Database::getConnection();
        
        require_once __DIR__ . '/../includes/db.php';
        syncCompletedMatchesReputation($conn, $nguoi_dung_id);

        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE nguoi_dung_id = ?");
        $stmt->bind_param("i", $nguoi_dung_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $info_success = null;
        $info_error = null;
        $password_success = null;
        $password_error = null;

        // Xử lý upload avatar (Direct file post hoặc form)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar_file'];
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $img_info = @getimagesize($file['tmp_name']);

            if (in_array($ext, $allowed_exts) && in_array($mime, $allowed_mimes) && $img_info !== false && $file['size'] <= 5 * 1024 * 1024) {
                $avatar_dir = __DIR__ . '/../images/avatar/';
                if (!is_dir($avatar_dir)) {
                    mkdir($avatar_dir, 0777, true);
                }
                
                $new_filename = 'avatar_' . $nguoi_dung_id . '_' . time() . '.' . $ext;
                $target_path = $avatar_dir . $new_filename;
                $db_path = 'images/avatar/' . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $av_stmt = $conn->prepare("UPDATE nguoi_dung SET avatar = ? WHERE nguoi_dung_id = ?");
                    $av_stmt->bind_param("si", $db_path, $nguoi_dung_id);
                    $av_stmt->execute();
                    $av_stmt->close();
                    
                    $_SESSION['avatar'] = $db_path;
                    $user['avatar'] = $db_path;
                    
                    if (!empty($_POST['ajax_upload'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'avatar' => $db_path, 'message' => 'Cập nhật ảnh đại diện thành công!']);
                        exit;
                    }
                    $info_success = "Cập nhật ảnh đại diện thành công!";
                } else {
                    if (!empty($_POST['ajax_upload'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Không thể lưu file ảnh tải lên.']);
                        exit;
                    }
                    $info_error = "Không thể lưu file ảnh tải lên.";
                }
            } else {
                if (!empty($_POST['ajax_upload'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Định dạng ảnh không hợp lệ hoặc dung lượng vượt quá 5MB.']);
                    exit;
                }
                $info_error = "Định dạng ảnh không hợp lệ hoặc dung lượng vượt quá 5MB.";
            }
        }
        
        // 1. Form Cập nhật thông tin cá nhân riêng biệt
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['update_profile_info']) || (isset($_POST['update_profile']) && empty($_POST['mk_cu']) && empty($_POST['mk_moi'])))) {
            $ho_ten = trim($_POST['ho_ten'] ?? '');
            $sdt = trim($_POST['sdt'] ?? '');
            $email = trim($_POST['email'] ?? '');

            // 1. Kiểm tra định dạng số điện thoại (đúng 10 chữ số)
            $phoneRegex = '/^[0-9]{10}$/';
            if (!preg_match($phoneRegex, $sdt)) {
                $info_error = "Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 chữ số.";
            }

            // 2. Kiểm tra định dạng Email
            $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|gov|info|biz|io|co|me|xyz|ai|pro|com\.vn|edu\.vn|gov\.vn|net\.vn|org\.vn)$/i';
            if (empty($info_error) && (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($emailRegex, $email))) {
                $info_error = "Email không hợp lệ, vui lòng kiểm tra và thử lại!";
            }

            // 3. Kiểm tra trùng lặp email với người dùng khác
            if (empty($info_error)) {
                $chk_stmt = $conn->prepare("SELECT nguoi_dung_id FROM nguoi_dung WHERE email = ? AND nguoi_dung_id != ?");
                $chk_stmt->bind_param("si", $email, $nguoi_dung_id);
                $chk_stmt->execute();
                if ($chk_stmt->get_result()->num_rows > 0) {
                    $info_error = "Email này đã được sử dụng bởi một tài khoản khác!";
                }
                $chk_stmt->close();
            }

            if (empty($info_error)) {
                $update_stmt = $conn->prepare("UPDATE nguoi_dung SET ho_ten=?, sdt=?, email=? WHERE nguoi_dung_id=?");
                $update_stmt->bind_param("sssi", $ho_ten, $sdt, $email, $nguoi_dung_id);
                if ($update_stmt->execute()) {
                    $_SESSION['ho_ten'] = $ho_ten;
                    $info_success = "Cập nhật thông tin cá nhân thành công!";
                    $user['ho_ten'] = $ho_ten;
                    $user['sdt'] = $sdt;
                    $user['email'] = $email;
                } else {
                    $info_error = "Có lỗi xảy ra khi cập nhật thông tin.";
                }
                $update_stmt->close();
            } else {
                $user['ho_ten'] = $ho_ten;
                $user['sdt'] = $sdt;
                $user['email'] = $email;
            }
        }

        // 2. Form Đổi mật khẩu bảo mật riêng biệt
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['change_password']) || (isset($_POST['update_profile']) && (!empty($_POST['mk_cu']) || !empty($_POST['mk_moi']))))) {
            $mk_cu = $_POST['mk_cu'] ?? '';
            $mk_moi = $_POST['mk_moi'] ?? '';
            $mk_xac_nhan = $_POST['mk_xac_nhan'] ?? '';

            if (empty($mk_cu) || empty($mk_moi) || empty($mk_xac_nhan)) {
                $password_error = "Vui lòng điền đầy đủ mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu.";
            } elseif (!password_verify($mk_cu, $user['mat_khau'])) {
                $password_error = "Mật khẩu hiện tại không chính xác.";
            } elseif ($mk_moi === $mk_cu || password_verify($mk_moi, $user['mat_khau'])) {
                $password_error = "Mật khẩu mới không được trùng với mật khẩu hiện tại!";
            } elseif ($mk_moi !== $mk_xac_nhan) {
                $password_error = "Xác nhận mật khẩu mới không khớp.";
            } elseif (strlen($mk_moi) < 6) {
                $password_error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
            } else {
                $mk_hash = password_hash($mk_moi, PASSWORD_DEFAULT);
                $pass_stmt = $conn->prepare("UPDATE nguoi_dung SET mat_khau=? WHERE nguoi_dung_id=?");
                $pass_stmt->bind_param("si", $mk_hash, $nguoi_dung_id);
                if ($pass_stmt->execute()) {
                    $password_success = "Đổi mật khẩu thành công!";
                    $user['mat_khau'] = $mk_hash;
                } else {
                    $password_error = "Có lỗi xảy ra khi cập nhật mật khẩu.";
                }
                $pass_stmt->close();
            }
        }

        // 3. Thống kê hoạt động của khách hàng
        $stat_stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN trang_thai = 'Đã xác nhận' THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN trang_thai = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled_bookings
            FROM dat_san 
            WHERE nguoi_dung_id = ? AND deleted_at IS NULL
        ");
        $stat_stmt->bind_param("i", $nguoi_dung_id);
        $stat_stmt->execute();
        $user_stats = $stat_stmt->get_result()->fetch_assoc();
        $stat_stmt->close();
        
        $stmt_ls = $conn->prepare("SELECT * FROM lich_su_uy_tin WHERE nguoi_dung_id = ? ORDER BY lich_su_id DESC LIMIT 100");
        $stmt_ls->bind_param("i", $nguoi_dung_id);
        $stmt_ls->execute();
        $reputation_history = $stmt_ls->get_result();
        $stmt_ls->close();
        
        $this->render('user/profile', [
            'user' => $user,
            'user_stats' => $user_stats,
            'reputation_history' => $reputation_history,
            'info_success' => $info_success,
            'info_error' => $info_error,
            'password_success' => $password_success,
            'password_error' => $password_error,
            'success' => $info_success ?: $password_success,
            'error' => $info_error ?: $password_error
        ]);
    }
    
    public function history() {
        if (!isset($_SESSION['nguoi_dung_id']) || ($_SESSION['vai_tro'] ?? '') !== 'khach_hang') {
            $this->redirect('index.php?controller=Auth&action=login');
        }
        
        $nguoi_dung_id = $_SESSION['nguoi_dung_id'];
        $conn = Database::getConnection();
        
        // Date & Status filter
        $tu_ngay = $_GET['tu_ngay'] ?? '';
        $den_ngay = $_GET['den_ngay'] ?? '';
        $trang_thai = $_GET['trang_thai'] ?? '';
        
        $min_date = '2025-01-01';
        if (!empty($tu_ngay) && $tu_ngay < $min_date) {
            $tu_ngay = $min_date;
        }
        if (!empty($den_ngay) && $den_ngay < $min_date) {
            $den_ngay = $min_date;
        }
        
        // Pagination
        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;
        
        // Base queries
        $where_clause = "WHERE ds.nguoi_dung_id = ? AND ds.deleted_at IS NULL";
        $params = [$nguoi_dung_id];
        $types = "i";
        
        if (!empty($tu_ngay)) {
            $where_clause .= " AND DATE(ds.ngay_dat) >= ?";
            $params[] = $tu_ngay;
            $types .= "s";
        }
        if (!empty($den_ngay)) {
            $where_clause .= " AND DATE(ds.ngay_dat) <= ?";
            $params[] = $den_ngay;
            $types .= "s";
        }
        if (!empty($trang_thai)) {
            $where_clause .= " AND ds.trang_thai = ?";
            $params[] = $trang_thai;
            $types .= "s";
        }
        
        // Get total records
        $count_sql = "SELECT COUNT(*) as total FROM dat_san ds $where_clause";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $limit);
        
        // Get paginated records
        $sql = "SELECT ds.*, sb.san_id, sb.ten_san, kg.gio_bat_dau, kg.gio_ket_thuc, kg.gia_tien, kg.gia_tien_san_7, 
                       tt.phuong_thuc, tt.trang_thai as tt_trang_thai,
                       dg.danh_gia_id, dg.so_sao, dg.noi_dung
                FROM dat_san ds 
                JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id 
                JOIN san_bong sb ON kg.san_id = sb.san_id 
                LEFT JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
                LEFT JOIN danh_gia dg ON ds.dat_san_id = dg.dat_san_id
                $where_clause
                ORDER BY ds.ngay_dat DESC, kg.gio_bat_dau DESC
                LIMIT ? OFFSET ?";
                
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $history = [];
        while($row = $res->fetch_assoc()) {
            $history[] = $row;
        }
        
        $this->render('user/history', [
            'history' => $history,
            'page' => $page,
            'total_pages' => $total_pages,
            'tu_ngay' => $tu_ngay,
            'den_ngay' => $den_ngay,
            'trang_thai' => $trang_thai
        ]);
    }

    public function cancelBooking() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['nguoi_dung_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện.']);
        }

        $nguoi_dung_id = $_SESSION['nguoi_dung_id'];
        $dat_san_id = intval($_POST['dat_san_id'] ?? 0);

        if ($dat_san_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Mã đơn đặt sân không hợp lệ.']);
        }

        $conn = Database::getConnection();

        // Lấy thông tin đơn đặt sân
        $stmt = $conn->prepare("
            SELECT ds.*, kg.gio_bat_dau, kg.gio_ket_thuc, sb.ten_san 
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            JOIN san_bong sb ON kg.san_id = sb.san_id
            WHERE ds.dat_san_id = ? AND ds.nguoi_dung_id = ? AND ds.deleted_at IS NULL
        ");
        $stmt->bind_param("ii", $dat_san_id, $nguoi_dung_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thông tin đơn đặt sân.']);
        }

        if ($booking['trang_thai'] === 'Đã hủy' || $booking['trang_thai'] === 'Hủy') {
            $this->jsonResponse(['success' => false, 'message' => 'Đơn đặt sân này đã được hủy trước đó.']);
        }

        if ($booking['trang_thai'] === 'Đã hoàn tiền') {
            $this->jsonResponse(['success' => false, 'message' => 'Đơn đặt sân này đã được hoàn tiền xong.']);
        }

        $match_start_time = strtotime($booking['ngay_dat'] . ' ' . $booking['gio_bat_dau']);
        // Chỉ chặn hủy đơn quá giờ với các đơn chưa hủy/chưa chuyển hoàn tiền
        if ($booking['trang_thai'] !== 'Chờ hoàn tiền' && $match_start_time <= time()) {
            $this->jsonResponse(['success' => false, 'message' => 'Không thể hủy đơn đặt sân đã hoặc đang diễn ra!']);
        }

        // --- Logic Trừ Điểm Uy Tín (Chỉ áp dụng nếu đơn đã được xác nhận) ---
        $penalty_points = 0;
        $penalty_msg = "";
        if ($booking['trang_thai'] === 'Đã xác nhận') {
            $hours_until_match = ($match_start_time - time()) / 3600;
            if ($hours_until_match <= 12) {
                $penalty_points = 15;
                $penalty_msg = " Bạn bị trừ 15 điểm uy tín do hủy sân sát giờ (dưới 12 tiếng).";
            } elseif ($hours_until_match <= 24) {
                $penalty_points = 5;
                $penalty_msg = " Bạn bị trừ 5 điểm uy tín do hủy sân sát ngày (dưới 24 tiếng).";
            }
        }

        if ($penalty_points > 0) {
            User::updateReputation($nguoi_dung_id, -$penalty_points, 'huy_san', "Hủy sân sát giờ thi đấu đơn #$dat_san_id");
        }
        // ---------------------------------------------------------------

        // Kiểm tra phương thức thanh toán: Nếu khách đã chuyển khoản (dù đơn ở Đã xác nhận, Chờ xác nhận, hay Chờ hoàn tiền) thì đều xử lý nhận STK hoàn tiền
        $stmt_pay = $conn->prepare("SELECT phuong_thuc FROM thanh_toan WHERE dat_san_id = ?");
        $stmt_pay->bind_param("i", $dat_san_id);
        $stmt_pay->execute();
        $pay_info = $stmt_pay->get_result()->fetch_assoc();
        $stmt_pay->close();

        $is_paid_transfer = (in_array($booking['trang_thai'], ['Đã xác nhận', 'Chờ xác nhận', 'Chờ hoàn tiền']) && $pay_info && $pay_info['phuong_thuc'] === 'chuyen_khoan');

        if ($is_paid_transfer) {
            $ngan_hang = trim($_POST['ngan_hang'] ?? '');
            $stk = trim($_POST['stk'] ?? '');
            $ten_chu_tk = trim($_POST['ten_chu_tk'] ?? '');
            $ly_do = trim($_POST['ly_do'] ?? '');

            if (empty($ngan_hang) || empty($stk) || empty($ten_chu_tk)) {
                $this->jsonResponse(['success' => false, 'require_refund_info' => true, 'message' => 'Vui lòng cung cấp đầy đủ thông tin tài khoản ngân hàng để nhận hoàn tiền.']);
            }

            $refund_data = json_encode([
                'ngan_hang' => $ngan_hang,
                'stk' => $stk,
                'ten_chu_tk' => mb_strtoupper($ten_chu_tk, 'UTF-8'),
                'ly_do' => $ly_do,
                'thoi_gian_yeu_cau' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);

            $stmt_cancel = $conn->prepare("UPDATE dat_san SET trang_thai = 'Chờ hoàn tiền', thong_tin_hoan_tien = ? WHERE dat_san_id = ?");
            $stmt_cancel->bind_param("si", $refund_data, $dat_san_id);
            if ($stmt_cancel->execute()) {
                $stmt_cancel->close();

                // Giải phóng slot khóa CSDL để khách hàng khác có thể đặt lại sân ngay lập tức
                $rel_stmt = $conn->prepare("UPDATE dat_san_slots SET trang_thai = 'released' WHERE dat_san_id = ?");
                if ($rel_stmt) {
                    $rel_stmt->bind_param("i", $dat_san_id);
                    $rel_stmt->execute();
                    $rel_stmt->close();
                }

                // Thông báo
                \Models\Notification::create(
                    $nguoi_dung_id,
                    "Yêu cầu hoàn tiền đơn #$dat_san_id",
                    "Yêu cầu hủy sân và hoàn tiền cho đơn " . $booking['ten_san'] . " đã được gửi tới quản trị viên. Sân đã được mở lại.",
                    'dat_san',
                    "index.php?controller=User&action=history"
                );

                $msg = ($booking['trang_thai'] === 'Chờ hoàn tiền') 
                    ? 'Thông tin nhận hoàn tiền đã được cập nhật thành công! Quản trị viên sẽ sớm chuyển khoản cho bạn.'
                    : 'Yêu cầu hủy sân và hoàn tiền đã được gửi thành công! Quản trị viên sẽ chuyển hoàn về STK của bạn sớm nhất. Sân đã được mở lại.' . $penalty_msg;

                $this->jsonResponse(['success' => true, 'message' => $msg]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Lỗi khi gửi yêu cầu hoàn tiền.']);
            }
        } else {
            // Cập nhật trạng thái thành Đã hủy
            $stmt_cancel = $conn->prepare("UPDATE dat_san SET trang_thai = 'Đã hủy' WHERE dat_san_id = ?");
            $stmt_cancel->bind_param("i", $dat_san_id);
            if ($stmt_cancel->execute()) {
                $stmt_cancel->close();

                // Giải phóng slot khóa CSDL để khách hàng khác có thể đặt lại sân ngay lập tức
                $rel_stmt = $conn->prepare("UPDATE dat_san_slots SET trang_thai = 'released' WHERE dat_san_id = ?");
                if ($rel_stmt) {
                    $rel_stmt->bind_param("i", $dat_san_id);
                    $rel_stmt->execute();
                    $rel_stmt->close();
                }

                // Thông báo
                \Models\Notification::create(
                    $nguoi_dung_id,
                    "Đã hủy đơn đặt sân #$dat_san_id",
                    "Bạn đã hủy đơn đặt sân " . $booking['ten_san'] . " ngày " . date('d/m/Y', strtotime($booking['ngay_dat'])) . ".",
                    'dat_san',
                    "index.php?controller=User&action=history"
                );

                $this->jsonResponse(['success' => true, 'message' => 'Hủy đơn đặt sân thành công! Sân đã được mở lại.' . $penalty_msg]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái hủy đơn.']);
            }
        }
    }
}
