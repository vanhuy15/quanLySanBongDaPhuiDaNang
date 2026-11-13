<?php

/**
 * Tạo nội dung HTML Email xác nhận đặt sân / hóa đơn
 */
function getBookingEmailHtml($booking, $type = 'confirmed') {
    $title = ($type === 'confirmed') ? "XÁC NHẬN ĐẶT SÂN THÀNH CÔNG" : (($type === 'created') ? "YÊU CẦU ĐẶT SÂN MỚI" : "THÔNG BÁO HỦY ĐƠN ĐẶT SÂN");
    $headerColor = ($type === 'confirmed') ? "#0d9488" : (($type === 'created') ? "#3b82f6" : "#dc2626");
    $badgeText = ($type === 'confirmed') ? "ĐÃ XÁC NHẬN" : (($type === 'created') ? "CHỜ XÁC NHẬN" : "ĐÃ HỦY");
    $badgeBg = ($type === 'confirmed') ? "#d1fae5" : (($type === 'created') ? "#dbeafe" : "#fee2e2");
    $badgeColor = ($type === 'confirmed') ? "#047857" : (($type === 'created') ? "#1d4ed8" : "#b91c1c");

    $hoTen = htmlspecialchars($booking['ho_ten'] ?? 'Quý khách');
    $tenSan = htmlspecialchars($booking['ten_san'] ?? '');
    $tenSanCon = htmlspecialchars($booking['ten_san_con'] ?? 'A');
    $diaChi = htmlspecialchars($booking['dia_chi'] ?? '');
    $ngayDat = date('d/m/Y', strtotime($booking['ngay_dat']));
    $gioBatDau = date('H:i', strtotime($booking['gio_bat_dau']));
    $gioKetThuc = date('H:i', strtotime($booking['gio_ket_thuc']));
    $giaTien = number_format($booking['gia_tien'] ?? 0);
    $datSanId = $booking['dat_san_id'] ?? '';

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>{$title}</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
            .header { background: {$headerColor}; color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 0.5px; }
            .content { padding: 30px 25px; }
            .greeting { font-size: 16px; margin-bottom: 20px; line-height: 1.6; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #f8fafc; border-radius: 8px; overflow: hidden; }
            .info-table tr { border-bottom: 1px solid #e2e8f0; }
            .info-table tr:last-child { border-bottom: none; }
            .info-table td { padding: 12px 16px; font-size: 14px; }
            .info-table td.label { font-weight: 600; color: #64748b; width: 40%; }
            .info-table td.value { font-weight: 600; color: #1e293b; text-align: right; }
            .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: {$badgeBg}; color: {$badgeColor}; }
            .total-box { background: #f1f5f9; padding: 15px 20px; border-radius: 8px; margin: 20px 0; display: flex; justify-content: space-between; align-items: center; }
            .total-title { font-size: 16px; font-weight: 700; color: #1e293b; }
            .total-amount { font-size: 20px; font-weight: 800; color: #0d9488; }
            .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
            .note { font-size: 13px; color: #64748b; line-height: 1.5; margin-top: 20px; padding: 12px; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⚽ {$title}</h1>
            </div>
            <div class='content'>
                <p class='greeting'>Xin chào <strong>{$hoTen}</strong>,</p>
                <p style='line-height: 1.6; font-size: 14px;'>
                    " . (($type === 'confirmed') ? "Đơn đặt sân bóng của bạn đã được Quản trị viên <strong>duyệt thành công</strong>. Dưới đây là thông tin chi tiết:" : (($type === 'created') ? "Chúng tôi đã tiếp nhận yêu cầu đặt sân của bạn. Chi tiết đơn đặt như sau:" : "Rất tiếc, đơn đặt sân của bạn đã bị hủy:")) . "
                </p>

                <table class='info-table'>
                    <tr>
                        <td class='label'>Mã đơn:</td>
                        <td class='value'>#{$datSanId}</td>
                    </tr>
                    <tr>
                        <td class='label'>Sân bóng:</td>
                        <td class='value'>{$tenSan} (Sân {$tenSanCon})</td>
                    </tr>
                    <tr>
                        <td class='label'>Địa chỉ:</td>
                        <td class='value'>{$diaChi}</td>
                    </tr>
                    <tr>
                        <td class='label'>Ngày thi đấu:</td>
                        <td class='value'>{$ngayDat}</td>
                    </tr>
                    <tr>
                        <td class='label'>Khung giờ:</td>
                        <td class='value'>{$gioBatDau} - {$gioKetThuc}</td>
                    </tr>
                    <tr>
                        <td class='label'>Trạng thái:</td>
                        <td class='value'><span class='badge'>{$badgeText}</span></td>
                    </tr>
                </table>

                <div class='total-box'>
                    <span class='total-title'>Tổng thanh toán:</span>
                    <span class='total-amount'>{$giaTien} VND</span>
                </div>

                " . (($type === 'confirmed') ? "
                <div class='note'>
                    <strong>Lưu ý:</strong> Vui lòng có mặt trước giờ đá 10-15 phút để chuẩn bị. Chúc bạn có những phút giây thi đấu tuyệt vời!
                </div>" : "") . "
            </div>
            <div class='footer'>
                <p>Hệ thống Quản lý và Đặt sân bóng đá trực tuyến</p>
                <p>Mọi thắc mắc xin vui lòng liên hệ Ban quản lý sân.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Tạo nội dung HTML Email thông báo đơn đặt sân mới gửi cho Admin
 */
function getAdminNewBookingEmailHtml($booking) {
    $hoTen = htmlspecialchars($booking['ho_ten'] ?? 'Khách hàng');
    $sdt = htmlspecialchars($booking['sdt'] ?? 'Chưa cung cấp');
    $email = htmlspecialchars($booking['email'] ?? 'Chưa cung cấp');
    $tenSan = htmlspecialchars($booking['ten_san'] ?? '');
    $tenSanCon = htmlspecialchars($booking['ten_san_con'] ?? 'A');
    $diaChi = htmlspecialchars($booking['dia_chi'] ?? '');
    $ngayDat = date('d/m/Y', strtotime($booking['ngay_dat']));
    $gioBatDau = date('H:i', strtotime($booking['gio_bat_dau']));
    $gioKetThuc = date('H:i', strtotime($booking['gio_ket_thuc']));
    $giaTien = number_format($booking['gia_tien'] ?? 0);
    $datSanId = $booking['dat_san_id'] ?? '';

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>THÔNG BÁO ĐƠN ĐẶT SÂN MỚI</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
            .header { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 25px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
            .content { padding: 25px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; background: #f8fafc; border-radius: 8px; overflow: hidden; }
            .info-table tr { border-bottom: 1px solid #e2e8f0; }
            .info-table tr:last-child { border-bottom: none; }
            .info-table td { padding: 10px 14px; font-size: 14px; }
            .info-table td.label { font-weight: 600; color: #64748b; width: 38%; }
            .info-table td.value { font-weight: 600; color: #1e293b; text-align: right; }
            .total-box { background: #fef3c7; padding: 12px 18px; border-radius: 8px; margin: 15px 0; display: flex; justify-content: space-between; align-items: center; border: 1px solid #fde68a; }
            .total-title { font-size: 15px; font-weight: 700; color: #92400e; }
            .total-amount { font-size: 18px; font-weight: 800; color: #b45309; }
            .btn-action { display: block; text-align: center; background: #0d9488; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 20px; }
            .footer { background: #f8fafc; padding: 15px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔔 ĐƠN ĐẶT SÂN MỚI CẦN DUYỆT</h1>
            </div>
            <div class='content'>
                <p style='font-size: 14px; line-height: 1.5;'>Hệ thống vừa nhận được đơn đặt sân mới từ khách hàng với thông tin chi tiết như sau:</p>
                
                <table class='info-table'>
                    <tr>
                        <td class='label'>Mã đơn:</td>
                        <td class='value'><strong>#{$datSanId}</strong></td>
                    </tr>
                    <tr>
                        <td class='label'>Khách hàng:</td>
                        <td class='value'>{$hoTen}</td>
                    </tr>
                    <tr>
                        <td class='label'>Số điện thoại:</td>
                        <td class='value'>{$sdt}</td>
                    </tr>
                    <tr>
                        <td class='label'>Email:</td>
                        <td class='value'>{$email}</td>
                    </tr>
                    <tr>
                        <td class='label'>Sân bóng:</td>
                        <td class='value'>{$tenSan} (Sân {$tenSanCon})</td>
                    </tr>
                    <tr>
                        <td class='label'>Ngày thi đấu:</td>
                        <td class='value'>{$ngayDat}</td>
                    </tr>
                    <tr>
                        <td class='label'>Khung giờ:</td>
                        <td class='value'>{$gioBatDau} - {$gioKetThuc}</td>
                    </tr>
                </table>

                <div class='total-box'>
                    <span class='total-title'>Số tiền thanh toán:</span>
                    <span class='total-amount'>{$giaTien} VND</span>
                </div>

                <p style='font-size: 13px; color: #64748b; margin-top: 15px;'>Vui lòng đăng nhập vào trang Quản trị để kiểm tra và duyệt đơn đặt sân này.</p>
            </div>
            <div class='footer'>
                <p>Hệ thống Thông báo Tự động - Quản lý Sân Bóng</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Email đặt lại mật khẩu (Reset Password)
 */
function getResetPasswordEmailHtml($ho_ten, $reset_link) {
    $hoTen = htmlspecialchars($ho_ten ?? 'Quý khách');
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Đặt lại mật khẩu</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
            .header { background: #6d28d9; color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 0.5px; }
            .content { padding: 30px 25px; }
            .greeting { font-size: 16px; margin-bottom: 20px; line-height: 1.6; }
            .notice-box { background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 14px; color: #92400e; }
            .btn-reset { display: inline-block; background: #6d28d9; color: white !important; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 16px; margin: 20px 0; }
            .link-text { font-size: 12px; color: #94a3b8; word-break: break-all; margin-top: 8px; }
            .footer { background: #f8fafc; padding: 16px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Đặt lại mật khẩu</h1>
            </div>
            <div class='content'>
                <p class='greeting'>Xin chào <strong>{$hoTen}</strong>,</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn trên Hệ Thống Đặt Sân Bóng.</p>
                <p>Nhấn vào nút bên dưới để đặt lại mật khẩu:</p>

                <div style='text-align: center;'>
                    <a href='{$reset_link}' class='btn-reset'>🔑 Đặt lại mật khẩu ngay</a>
                </div>

                <div class='notice-box'>
                    ⚠️ <strong>Lưu ý quan trọng:</strong>
                    <ul style='margin: 8px 0 0 0; padding-left: 18px;'>
                        <li>Link này chỉ có hiệu lực trong <strong>1 giờ</strong>.</li>
                        <li>Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.</li>
                        <li>Không chia sẻ link này với bất kỳ ai.</li>
                    </ul>
                </div>

                <p class='link-text'>Nếu nút không hoạt động, copy link sau vào trình duyệt:<br>{$reset_link}</p>
            </div>
            <div class='footer'>
                <p>Email này được gửi tự động — Hệ Thống Đặt Sân Bóng</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
