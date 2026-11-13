<?php
namespace Controllers;

use Core\Controller;
use Config\Database;
use Models\SanBong;
use Models\DatSan;
use Models\KhungGio;
use Models\User;

class DatSanController extends Controller {

    public function book() {
        if (!isset($_SESSION['nguoi_dung_id']) || ($_SESSION['vai_tro'] ?? '') !== 'khach_hang') {
            $_SESSION['flash_error'] = 'Vui lòng đăng nhập tài khoản người dùng để thực hiện đặt sân!';
            $this->redirect('index.php?controller=Auth&action=login');
        }

        $san_bongs = SanBong::getAll();

        $san_id_xem = isset($_GET['san_id']) ? intval($_GET['san_id']) : 0;
        if ($san_id_xem == 0 && count($san_bongs) > 0) {
            $san_id_xem = $san_bongs[0]['san_id'];
        }
        $ngay_dat_xem = isset($_GET['ngay_dat']) ? $_GET['ngay_dat'] : date('Y-m-d');

        $this->render('datsan/book', [
            'san_bongs' => $san_bongs,
            'san_id_xem' => $san_id_xem,
            'ngay_dat_xem' => $ngay_dat_xem
        ]);
    }

    public function getSchedule() {
        // AJAX endpoint returning HTML
        $san_id = intval($_GET['san_id'] ?? 0);
        $ngay_dat = $_GET['ngay_dat'] ?? date('Y-m-d');
        $view_mode = $_GET['mode'] ?? 'san_5'; // 'san_5' or 'san_7'
        
        $conn = Database::getConnection();

        // Cleanup đơn quá hạn — dùng centralized method thay vì copy-paste query
        Database::cleanupExpiredBookings();
        
        $san_info_stmt = $conn->prepare("SELECT ten_san, so_luong, loai_san, quy_tac_ghep FROM san_bong WHERE san_id = ?");
        $san_info_stmt->bind_param("i", $san_id);
        $san_info_stmt->execute();
        $san_info = $san_info_stmt->get_result()->fetch_assoc();
        
        if (!$san_info) {
            echo "<p>Không tìm thấy sân.</p>";
            exit;
        }
        
        $ten_san_xem = $san_info['ten_san'];
        $tong_so_san = (int)$san_info['so_luong'];
        $loai_san_goc = $san_info['loai_san'];
        $quy_tac_ghep = (int)$san_info['quy_tac_ghep'];

        // Sân chuyên 7 người
        if ($loai_san_goc === 'san_7') {
            $view_mode = 'san_7';
        }
        
        $stmt_kg = $conn->prepare("SELECT * FROM khung_gio WHERE san_id = ? ORDER BY gio_bat_dau");
        $stmt_kg->bind_param("i", $san_id);
        $stmt_kg->execute();
        $khung_gio_list = $stmt_kg->get_result();
        $sub_pitches = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        // Xây dựng danh sách các cụm sân 7 ghép
        $groups_san_7 = [];
        if ($loai_san_goc === 'san_5_7' && $quy_tac_ghep > 0) {
            $step = $quy_tac_ghep;
            $group_num = 1;
            for ($idx = 0; $idx + $step <= $tong_so_san; $idx += $step) {
                $components = array_slice($sub_pitches, $idx, $step);
                $group_name = "Sân 7." . $group_num . " (" . implode('+', $components) . ")";
                $groups_san_7[] = [
                    'name' => $group_name,
                    'components' => $components,
                    'key' => implode(',', $components),
                    'num' => $group_num
                ];
                $group_num++;
            }
        } elseif ($loai_san_goc === 'san_7') {
            for ($i = 0; $i < $tong_so_san; $i++) {
                $pname = "Sân 7" . ($sub_pitches[$i] ?? ($i+1));
                $groups_san_7[] = [
                    'name' => $pname,
                    'components' => [$pname],
                    'key' => $pname,
                    'num' => ($i + 1)
                ];
            }
        }
        
        $this->render('datsan/schedule', [
            'ten_san_xem'    => $ten_san_xem,
            'ngay_dat'        => $ngay_dat,
            'loai_san_goc'    => $loai_san_goc,
            'quy_tac_ghep'    => $quy_tac_ghep,
            'view_mode'       => $view_mode,
            'khung_gio_list'  => $khung_gio_list,
            'tong_so_san'     => $tong_so_san,
            'sub_pitches'     => $sub_pitches,
            'groups_san_7'    => $groups_san_7
        ]);
    }

    public function bookSubmit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['nguoi_dung_id']) || ($_SESSION['vai_tro'] ?? '') !== 'khach_hang') {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập tài khoản người dùng để đặt sân']);
        }

        $nguoi_dung_id = $_SESSION['nguoi_dung_id'];
        $khung_gio_id = intval($_POST['khung_gio_id'] ?? 0);
        $ngay_dat = $_POST['ngay_dat'] ?? '';
        $loai_san_dat = $_POST['loai_san_dat'] ?? 'san_5';
        $ten_san_con = trim($_POST['ten_san_con'] ?? '');
        $san_con_khoa = trim($_POST['san_con_khoa'] ?? $ten_san_con);

        if (empty($ten_san_con)) {
            $this->jsonResponse(['success' => false, 'message' => 'Lỗi: Bạn chưa chọn sân con. Vui lòng click vào một sân trống trên sơ đồ trước khi bấm Đặt Ngay!']);
        }

        $result = DatSan::bookWithLock($nguoi_dung_id, $khung_gio_id, $ngay_dat, $loai_san_dat, $ten_san_con, $san_con_khoa);
        $this->jsonResponse($result);
    }
}

