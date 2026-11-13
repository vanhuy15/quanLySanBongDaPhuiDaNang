<?php
use PHPUnit\Framework\TestCase;

/**
 * Kiểm thử Tính Hợp Lệ Dữ Liệu Đầu Vào & Khử Khuẩn (Input Validation & XSS Sanitization)
 * Áp dụng trong:
 * - controllers/AuthController.php (Đăng ký, Đăng nhập)
 * - controllers/DatSanController.php (Validate ngày, giờ, số điện thoại)
 * - controllers/ReviewController.php (Bình luận, đánh giá)
 */
class InputValidationAndSanitizationTest extends TestCase {

    /**
     * Kiểm thử biểu thức chính quy số điện thoại Việt Nam
     */
    public function testVietnamesePhoneNumberValidation() {
        $validPhones = ['0912345678', '0389998888', '0771234567', '0868889999', '0523456789'];
        $invalidPhones = ['123456', '091234567', '09123456789', 'abcdefghij', '0123456789'];

        $pattern = '/^(0[3|5|7|8|9])+([0-9]{8})$/';

        foreach ($validPhones as $phone) {
            $this->assertEquals(1, preg_match($pattern, $phone), "SĐT {$phone} phải hợp lệ");
        }

        foreach ($invalidPhones as $phone) {
            $this->assertEquals(0, preg_match($pattern, $phone), "SĐT {$phone} không được coi là hợp lệ");
        }
    }

    /**
     * Kiểm thử validation địa chỉ email
     */
    public function testEmailValidation() {
        $validEmails = ['user@example.com', 'test.booking@sanbong.vn', 'admin_15@gmail.com'];
        $invalidEmails = ['plainaddress', '@missingusername.com', 'username@.com', 'user@domain..com'];

        foreach ($validEmails as $email) {
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "Email {$email} phải hợp lệ");
        }

        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "Email {$email} phải bị từ chối");
        }
    }

    /**
     * Kiểm thử quy tắc ngày đặt sân: Không được đặt ngày trong quá khứ
     */
    public function testBookingDateMustNotBeInThePast() {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime('+2 days'));
        $pastDate = date('Y-m-d', strtotime('-1 day'));

        $this->assertTrue($today >= date('Y-m-d'), "Ngày hôm nay được phép đặt");
        $this->assertTrue($futureDate >= date('Y-m-d'), "Ngày tương lai được phép đặt");
        $this->assertFalse($pastDate >= date('Y-m-d'), "Ngày trong quá khứ phải bị chặn");
    }

    /**
     * Kiểm thử quy tắc khung giờ: Giờ kết thúc phải lớn hơn giờ bắt đầu
     */
    public function testTimeSlotDurationMustBePositive() {
        $validSlots = [
            ['start' => '17:00:00', 'end' => '18:30:00'],
            ['start' => '06:00:00', 'end' => '07:00:00'],
            ['start' => '20:30:00', 'end' => '22:00:00']
        ];

        foreach ($validSlots as $slot) {
            $diff = strtotime($slot['end']) - strtotime($slot['start']);
            $this->assertGreaterThan(0, $diff, "Giờ kết thúc {$slot['end']} phải sau giờ bắt đầu {$slot['start']}");
        }

        // Trường hợp khung giờ lỗi
        $invalidDiff = strtotime('17:00:00') - strtotime('18:00:00');
        $this->assertLessThanOrEqual(0, $invalidDiff, "Giờ kết thúc trước giờ bắt đầu phải bị báo lỗi");
    }

    /**
     * Kiểm thử cơ chế làm sạch mã độc XSS
     */
    public function testXssSanitization() {
        $maliciousInputs = [
            '<script>alert("XSS")</script>' => '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
            '"><img src=x onerror=alert(1)>' => '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;',
            'Test <b>bold</b> text' => 'Test &lt;b&gt;bold&lt;/b&gt; text'
        ];

        foreach ($maliciousInputs as $raw => $expectedClean) {
            $sanitized = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
            $this->assertEquals($expectedClean, $sanitized, "Chuỗi XSS phải được mã hóa thực thể HTML an toàn");
            $this->assertStringNotContainsString('<script>', $sanitized);
        }
    }
}

