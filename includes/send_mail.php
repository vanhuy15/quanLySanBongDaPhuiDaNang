<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendEmail($to, $subject, $content) {
    // Nạp .env nếu chưa có
    $env_file = __DIR__ . '/../.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\'');
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    $mail = new PHPMailer(true);

    try {
        $mailHost = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $mailUser = getenv('MAIL_USERNAME') ?: '';
        $mailPass = getenv('MAIL_PASSWORD') ?: '';
        $mailPort = intval(getenv('MAIL_PORT')) ?: 587;

        if (empty($mailUser) || empty($mailPass) || strpos($mailUser, 'your_email') !== false || strpos($mailPass, 'your_app_password') !== false) {
            error_log("PHPMailer Warning: SMTP credentials in .env are not configured! (MAIL_USERNAME/MAIL_PASSWORD)");
            return false;
        }

        // 1. Cấu hình Server từ biến môi trường
        $mail->isSMTP();                                            
        $mail->Host       = $mailHost;                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = $mailUser;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $mailPort;
        $mail->Timeout    = 3; // Giảm timeout xuống 3s để phản hồi nhanh chóng
        $mail->SMTPAutoTLS = true;

        // 2. Cấu hình người gửi/nhận
        $fromEmail = getenv('MAIL_FROM') ?: (getenv('MAIL_USERNAME') ?: 'no-reply@quanlysanbong.com');
        $fromName  = getenv('MAIL_FROM_NAME') ?: 'He Thong Dat San Bong';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        // 3. Nội dung
        $mail->isHTML(true);                                  
        $mail->Subject = $subject;
        $mail->Body    = $content;
        $mail->CharSet = 'UTF-8';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending error: " . $mail->ErrorInfo);
        return false;
    }
}
?>