<?php
// Secure Session Cookie Configuration (Fixes OWASP ZAP Cookie HttpOnly & SameSite)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Global Security Response Headers (Fixes OWASP ZAP Missing Headers & CSP)
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com https://cdnjs.cloudflare.com https://img.vietqr.io data:; img-src 'self' data: https: https://img.vietqr.io blob:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; connect-src 'self' https:;");
@header_remove('X-Powered-By');

// Autoloading simple
spl_autoload_register(function ($class) {
    // Convert namespace to path
    $path = str_replace('\\', '/', $class) . '.php';
    if (file_exists(__DIR__ . '/' . $path)) {
        require_once __DIR__ . '/' . $path;
    } else {
        $path_lower = strtolower(dirname($path)) . '/' . basename($path);
        if (file_exists(__DIR__ . '/' . $path_lower)) {
            require_once __DIR__ . '/' . $path_lower;
        }
    }
});

// Load .env
$dotenv = new \Core\DotEnv(__DIR__ . '/.env');
$dotenv->load();

// Load CSRF Helper
require_once __DIR__ . '/includes/csrf.php';

// Global CSRF Validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        die("Lỗi 403: Yêu cầu bị từ chối do thiếu hoặc sai mã bảo vệ (CSRF Token). Vui lòng tải lại trang và thử lại.");
    }
}

// Initialize Router
$router = new \Core\Router();
$router->dispatch($_SERVER['REQUEST_URI']);