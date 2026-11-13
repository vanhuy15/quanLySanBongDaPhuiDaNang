<?php
namespace Controllers;

use Core\Controller;
use Config\Database;
use Models\User;

class AuthController extends Controller {
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isApi = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false 
                     || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
                     || (isset($_GET['format']) && $_GET['format'] === 'json'));

            // Rate Limiting: Chặn brute-force sau 5 lần thử sai trong 15 phút
            if (\Core\RateLimiter::isLimited('login', 5, 900)) {
                $wait = \Core\RateLimiter::remainingSeconds('login');
                $error = "Bạn đã thử đăng nhập sai quá nhiều lần. Vui lòng chờ {$wait} giây trước khi thử lại!";
                if ($isApi) {
                    $this->jsonResponse(['success' => false, 'message' => $error], 429);
                    return;
                }
                $this->render('auth/login', ['error' => $error]);
                return;
            }

            $username = trim($_POST['username'] ?? $_POST['ten_dang_nhap'] ?? '');
            $password = $_POST['password'] ?? $_POST['mat_khau'] ?? '';
            
            // Validate username format (Chống SQL Injection & Input malformed)
            if (empty($username) || !preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username)) {
                $error = 'Tên đăng nhập không hợp lệ hoặc không tồn tại!';
                if ($isApi) {
                    $this->jsonResponse(['success' => false, 'message' => $error], 400);
                    return;
                }
                $this->render('auth/login', ['error' => $error]);
                return;
            }

            $user = User::findByUsername($username);

            // Kiểm tra khớp tuyệt đối từng ký tự chữ thường / chữ hoa
            if ($user && $user['ten_dang_nhap'] !== $username) {
                $user = null;
            }
            
            if ($user) {
                if ($user['trang_thai'] === 'banned') {
                    $error = "Tài khoản của bạn đã bị khóa!";
                } elseif (password_verify($password, $user['mat_khau'])) {
                    // Chống Session Fixation: Tạo session ID mới sau khi đăng nhập thành công
                    session_regenerate_id(true);

                    $_SESSION['nguoi_dung_id'] = $user['nguoi_dung_id'];
                    $_SESSION['vai_tro'] = $user['vai_tro'];
                    $_SESSION['ho_ten'] = $user['ho_ten'];
                    $_SESSION['avatar'] = !empty($user['avatar']) ? $user['avatar'] : 'images/avatar/default_avatar.svg';

                    \Core\RateLimiter::clear('login');
                    \Core\Logger::info("Đăng nhập thành công: {$user['ten_dang_nhap']} (Vai trò: {$user['vai_tro']})");

                    // Hỗ trợ trả JSON Token cho Postman / Mobile API Clients
                    if ($isApi) {
                        $this->jsonResponse([
                            'success' => true,
                            'message' => 'Đăng nhập thành công',
                            'token' => session_id(),
                            'csrf_token' => $_SESSION['csrf_token'] ?? '',
                            'user' => [
                                'id' => $user['nguoi_dung_id'],
                                'username' => $user['ten_dang_nhap'],
                                'role' => $user['vai_tro'],
                                'fullname' => $user['ho_ten']
                            ]
                        ]);
                        return;
                    }

                    if ($user['vai_tro'] === 'admin') {
                        $this->redirect('admin/index.php');
                    } else {
                        $this->redirect('index.php');
                    }
                } else {
                    \Core\RateLimiter::hit('login', 900);
                    \Core\Logger::warning("Đăng nhập thất bại: Sai mật khẩu cho user {$username}");
                    $error = "Mật khẩu không chính xác!";
                }
            } else {
                \Core\RateLimiter::hit('login', 900);
                \Core\Logger::warning("Đăng nhập thất bại: Tên đăng nhập không tồn tại {$username}");
                $error = "Tên đăng nhập không tồn tại!";
            }
            
            if ($isApi) {
                $this->jsonResponse(['success' => false, 'message' => $error], 401);
                return;
            }

            $this->render('auth/login', ['error' => $error]);
        } else {
            $success = $_SESSION['flash_success'] ?? null;
            $error = $_SESSION['flash_error'] ?? null;
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
            $this->render('auth/login', ['success' => $success, 'error' => $error]);
        }
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['ten_dang_nhap'] ?? $_POST['username'] ?? '');
            $password = $_POST['mat_khau'] ?? $_POST['password'] ?? '';
            $re_password = $_POST['nhap_lai_mat_khau'] ?? $_POST['confirm_password'] ?? '';
            $hoten = trim(strip_tags($_POST['ho_ten'] ?? ''));
            $sdt = trim($_POST['sdt'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($username) || empty($password) || empty($hoten) || empty($sdt) || empty($email)) {
                $this->render('auth/register', ['error' => "Vui lòng điền đầy đủ tất cả các thông tin!"]);
                return;
            }

            if (mb_strlen($hoten) > 100) {
                $this->render('auth/register', ['error' => "Họ và tên không được vượt quá 100 ký tự!"]);
                return;
            }

            // Kiểm tra định dạng tên đăng nhập
            if (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username)) {
                $this->render('auth/register', ['error' => "Tên đăng nhập chỉ được chứa chữ cái, số, dấu gạch dưới (từ 3 đến 30 ký tự)!"]);
                return;
            }
            
            if (!empty($re_password) && $password !== $re_password) {
                $this->render('auth/register', ['error' => "Mật khẩu xác nhận không khớp!"]);
                return;
            }
            
            if (strlen($password) < 6) {
                $this->render('auth/register', ['error' => "Mật khẩu phải có ít nhất 6 ký tự!"]);
                return;
            }

            // Kiểm tra Số điện thoại (10 chữ số)
            if (!preg_match('/^[0-9]{10}$/', $sdt)) {
                $this->render('auth/register', ['error' => "Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 chữ số."]);
                return;
            }

            // Kiểm tra Email
            $emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|vn|net|org|edu|gov|info|biz|io|co|me|xyz|ai|pro|com\.vn|edu\.vn|gov\.vn|net\.vn|org\.vn)$/i';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($emailRegex, $email)) {
                $this->render('auth/register', ['error' => "Email không hợp lệ, vui lòng kiểm tra và thử lại!"]);
                return;
            }
            
            if (User::findByUsername($username) || User::findByEmail($email)) {
                $this->render('auth/register', ['error' => "Tên đăng nhập hoặc Email đã tồn tại!"]);
                return;
            }
            
            $hash_pass = password_hash($password, PASSWORD_DEFAULT);
            $new_uid = User::create($username, $hash_pass, $hoten, $email, $sdt, 'khach_hang');
            
            if ($new_uid) {
                User::updateReputation($new_uid, 0, 'khoi_tao', 'Khởi tạo tài khoản thành viên mới');

                $_SESSION['flash_success'] = "Đăng ký tài khoản thành công! Vui lòng đăng nhập.";
                $this->redirect('index.php?controller=Auth&action=login');
            } else {
                $this->render('auth/register', ['error' => "Lỗi hệ thống khi đăng ký tài khoản. Vui lòng thử lại!"]);
            }
        } else {
            $this->render('auth/register');
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_unset();
        session_destroy();
        
        $this->redirect('index.php?controller=Auth&action=login');
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Rate Limiting: Chống spam email quên mật khẩu (Tối đa 3 lần / 15 phút)
            if (\Core\RateLimiter::isLimited('forgot_password', 3, 900)) {
                $wait = \Core\RateLimiter::remainingSeconds('forgot_password');
                $this->render('auth/forgot', [
                    'error' => "Bạn đã yêu cầu đặt lại mật khẩu quá nhiều lần. Vui lòng chờ {$wait} giây trước khi gửi tiếp."
                ]);
                return;
            }

            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $this->render('auth/forgot', ['error' => 'Vui lòng nhập địa chỉ email!']);
                return;
            }

            \Core\RateLimiter::hit('forgot_password', 900);
            $conn = Database::getConnection();

            // Luôn hiện thông báo thành công để tránh email enumeration attack
            $success = "Nếu email tồn tại trong hệ thống, bạn sẽ nhận được link đặt lại mật khẩu trong vài phút. Vui lòng kiểm tra hộp thư (kể cả Spam).";

            $stmt = $conn->prepare("SELECT nguoi_dung_id, ho_ten FROM nguoi_dung WHERE email = ? AND trang_thai != 'inactive'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user) {
                // Tạo token bảo mật 64 ký tự
                $token    = bin2hex(random_bytes(32));
                $het_han  = date('Y-m-d H:i:s', time() + 3600); // Hết hạn sau 1 giờ

                // Xóa token cũ của email này (nếu có)
                $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $del_stmt->bind_param("s", $email);
                $del_stmt->execute();
                $del_stmt->close();

                // Lưu token mới vào DB
                $ins_stmt = $conn->prepare("INSERT INTO password_resets (email, token, het_han) VALUES (?, ?, ?)");
                $ins_stmt->bind_param("sss", $email, $token, $het_han);

                if ($ins_stmt->execute()) {
                    $ins_stmt->close();
                    \Core\Logger::info("Yêu cầu reset mật khẩu cho email: {$email}");

                    // Tạo link reset
                    $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $reset_link = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/quanlysanbong/index.php?controller=Auth&action=resetPassword&token=' . $token;

                    // Gửi email qua PHPMailer
                    try {
                        require_once __DIR__ . '/../includes/send_mail.php';
                        require_once __DIR__ . '/../includes/email_templates.php';
                        $emailBody = getResetPasswordEmailHtml($user['ho_ten'], $reset_link);
                        sendEmail($email, 'Đặt lại mật khẩu - Hệ Thống Sân Bóng', $emailBody);
                    } catch (\Throwable $e) {
                        error_log("Reset password email error: " . $e->getMessage());
                    }
                } else {
                    $ins_stmt->close();
                }
            }

            $this->render('auth/forgot', ['success' => $success]);
        } else {
            $this->render('auth/forgot');
        }
    }

    public function resetPassword() {
        $token = trim($_GET['token'] ?? '');

        if (empty($token)) {
            $this->render('auth/reset', ['error' => 'Link đặt lại mật khẩu không hợp lệ!', 'token' => '']);
            return;
        }

        $conn = Database::getConnection();

        // Kiểm tra token còn hợp lệ không (hỗ trợ cả het_han và fallback created_at trong vòng 1 giờ)
        $col_check = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'het_han'");
        if ($col_check && $col_check->num_rows > 0) {
            $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND (het_han > NOW() OR (het_han IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)))");
        } else {
            $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$reset) {
            $this->render('auth/reset', [
                'error' => 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn (1 giờ). Vui lòng gửi lại yêu cầu mới.',
                'token' => ''
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_pass     = $_POST['mat_khau_moi'] ?? '';
            $confirm_pass = $_POST['xac_nhan_mat_khau'] ?? '';

            if (strlen($new_pass) < 6) {
                $this->render('auth/reset', ['error' => 'Mật khẩu phải có ít nhất 6 ký tự!', 'token' => $token]);
                return;
            }
            if ($new_pass !== $confirm_pass) {
                $this->render('auth/reset', ['error' => 'Mật khẩu xác nhận không khớp!', 'token' => $token]);
                return;
            }

            // Kiểm tra mật khẩu mới không được trùng với mật khẩu trước đó
            $chk_stmt = $conn->prepare("SELECT mat_khau FROM nguoi_dung WHERE email = ?");
            $chk_stmt->bind_param("s", $reset['email']);
            $chk_stmt->execute();
            $curr_user = $chk_stmt->get_result()->fetch_assoc();
            $chk_stmt->close();

            if ($curr_user && (password_verify($new_pass, $curr_user['mat_khau']) || $new_pass === $curr_user['mat_khau'])) {
                $this->render('auth/reset', [
                    'error' => 'Mật khẩu mới không được trùng với mật khẩu trước đó của bạn. Vui lòng chọn một mật khẩu khác!',
                    'token' => $token
                ]);
                return;
            }

            $hash = password_hash($new_pass, PASSWORD_DEFAULT);

            // Cập nhật mật khẩu mới
            $upd_stmt = $conn->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE email = ?");
            $upd_stmt->bind_param("ss", $hash, $reset['email']);

            if ($upd_stmt->execute()) {
                $upd_stmt->close();

                // Xóa token đã dùng
                $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $del_stmt->bind_param("s", $token);
                $del_stmt->execute();
                $del_stmt->close();

                $_SESSION['flash_success'] = 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.';
                $this->redirect('index.php?controller=Auth&action=login');
            } else {
                $upd_stmt->close();
                $this->render('auth/reset', ['error' => 'Lỗi hệ thống, vui lòng thử lại!', 'token' => $token]);
            }
        } else {
            $this->render('auth/reset', ['token' => $token]);
        }
    }
}
