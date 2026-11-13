<?php
/**
 * Database Bridge - Admin Panel
 * -------------------------------------------------------
 * Dùng Config\Database singleton để nhất quán với Frontend MVC.
 * Admin panel giờ dùng chung connection pool với Frontend.
 * -------------------------------------------------------
 */

// Tải autoloader nếu các class MVC chưa được load (VD: khi admin gọi trực tiếp)
if (!class_exists('Config\\Database')) {
    $__project_root = dirname(__DIR__);

    spl_autoload_register(function ($class) use ($__project_root) {
        $path = str_replace('\\', '/', $class) . '.php';
        if (file_exists($__project_root . '/' . $path)) {
            require_once $__project_root . '/' . $path;
        } else {
            $path_lower = strtolower(dirname($path)) . '/' . basename($path);
            if (file_exists($__project_root . '/' . $path_lower)) {
                require_once $__project_root . '/' . $path_lower;
            }
        }
    });
}

// Load .env nếu chưa load
if (!getenv('DB_NAME')) {
    $__env_path = dirname(__DIR__) . '/.env';
    if (file_exists($__env_path)) {
        $__dotenv = new \Core\DotEnv($__env_path);
        $__dotenv->load();
    }
}

// Singleton connection — nhất quán với Frontend MVC
$conn = \Config\Database::getConnection();

// Load CSRF Helper
require_once dirname(__DIR__) . '/includes/csrf.php';

// Global CSRF Validation for Admin POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        die("Lỗi 403: Yêu cầu bị từ chối do thiếu hoặc sai mã bảo vệ (CSRF Token). Vui lòng tải lại trang và thử lại.");
    }
}


/**
 * Làm sạch input
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Kiểm tra đăng nhập
 */
function is_logged_in() {
    return isset($_SESSION['nguoi_dung_id']);
}

/**
 * Kiểm tra quyền admin
 */
function is_admin() {
    return isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'admin';
}

function get_app_base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(.*?quanlysanbong)#i', $script, $m)) {
        return $m[1];
    }
    $dir = rtrim(dirname($script), '/\\');
    if (strpos($dir, '/admin') !== false) {
        $dir = substr($dir, 0, strpos($dir, '/admin'));
    }
    return $dir ?: '';
}

function require_login() {
    if (!is_logged_in()) {
        $base = get_app_base_url();
        $target = $base . '/index.php?controller=Auth&action=login';
        if (!headers_sent()) {
            header("Location: " . $target);
        } else {
            echo "<script>window.location.href='" . htmlspecialchars($target, ENT_QUOTES) . "';</script>";
        }
        exit();
    }
}

/**
 * Redirect nếu không phải admin
 */
function require_admin() {
    if (!is_admin()) {
        $base = get_app_base_url();
        $target = $base . '/index.php';
        if (!headers_sent()) {
            header("Location: " . $target);
        } else {
            echo "<script>window.location.href='" . htmlspecialchars($target, ENT_QUOTES) . "';</script>";
        }
        exit();
    }
}

/**
 * Tự động cộng +5 điểm uy tín khi trận đấu đã thực sự diễn ra xong (hết giờ đá)
 */
function syncCompletedMatchesReputation($conn, $user_id = null) {
    if (!$conn) return;
    $where_user = $user_id ? " AND ds.nguoi_dung_id = " . intval($user_id) : "";
    
    $sql = "SELECT ds.dat_san_id, ds.nguoi_dung_id, sb.ten_san, ds.ngay_dat, kg.gio_ket_thuc,
                   CONCAT(ds.ngay_dat, ' ', kg.gio_ket_thuc) as match_end_time
            FROM dat_san ds
            JOIN khung_gio kg ON ds.khung_gio_id = kg.khung_gio_id
            JOIN san_bong sb ON kg.san_id = sb.san_id
            JOIN thanh_toan tt ON ds.dat_san_id = tt.dat_san_id
            WHERE ds.trang_thai = 'Đã xác nhận' 
              AND tt.trang_thai = 'da_thanh_toan'
              AND CONCAT(ds.ngay_dat, ' ', kg.gio_ket_thuc) <= NOW()
              $where_user
              AND NOT EXISTS (
                  SELECT 1 FROM lich_su_uy_tin lsu 
                  WHERE lsu.nguoi_dung_id = ds.nguoi_dung_id 
                    AND lsu.loai_hanh_dong = 'hoan_tat_dat_san'
                    AND lsu.ghi_chu LIKE CONCAT('%#', ds.dat_san_id, ' %')
              )";
              
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $u_id = (int)$row['nguoi_dung_id'];
            $b_id = (int)$row['dat_san_id'];
            $p_short = preg_replace('/^Sân bóng đá\s*/iu', '', $row['ten_san']);
            $p_short = str_replace('Trường Đại Học Sư Phạm-Đà Nẵng', 'ĐH Sư Phạm', $p_short);
            
            $conn->query("UPDATE nguoi_dung SET diem_uy_tin = LEAST(100, diem_uy_tin + 5) WHERE nguoi_dung_id = $u_id");
            $cur_score = (int)($conn->query("SELECT diem_uy_tin FROM nguoi_dung WHERE nguoi_dung_id = $u_id")->fetch_row()[0] ?? 100);
            
            $note = "Hoàn thành đơn #$b_id ($p_short)";
            $stmt = $conn->prepare("INSERT INTO lich_su_uy_tin (nguoi_dung_id, so_diem, diem_sau_cung, loai_hanh_dong, ghi_chu, ngay_tao) VALUES (?, 5, ?, 'hoan_tat_dat_san', ?, ?)");
            $stmt->bind_param("iiss", $u_id, $cur_score, $note, $row['match_end_time']);
            $stmt->execute();
            $stmt->close();
        }
    }
}