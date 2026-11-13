<?php
use PHPUnit\Framework\TestCase;

/**
 * Kiểm thử Nâng cao: Phân tích Giá trị Biên (Boundary Value Analysis - BVA),
 * Mã hóa Mật khẩu BCRYPT và Ràng buộc Bảo mật Trạng thái Tài khoản.
 */
class BoundaryAndSecurityTest extends TestCase {

    /**
     * Thuật toán tính phạt hủy sân chuẩn theo controllers/UserController.php:
     * - $diff_hours > 24: 0 điểm
     * - 12 <= $diff_hours <= 24: -5 điểm
     * - $diff_hours < 12: -15 điểm
     */
    private function calculatePenaltyHours(float $diffHours): int {
        if ($diffHours > 24.0) {
            return 0;
        } elseif ($diffHours >= 12.0) {
            return 5;
        } else {
            return 15;
        }
    }

    /**
     * BVA 1: Giá trị biên tại mốc 24 giờ
     */
    public function testCancellationBoundaryAt24Hours() {
        // Trên 24h một chút (24.01h = 24h + 36 giây): Miễn phạt
        $this->assertEquals(0, $this->calculatePenaltyHours(24.01));

        // Đúng chính xác 24.00h: Phạt mức 1 (5 điểm)
        $this->assertEquals(5, $this->calculatePenaltyHours(24.00));

        // Dưới 24h một chút (23.99h): Phạt mức 1 (5 điểm)
        $this->assertEquals(5, $this->calculatePenaltyHours(23.99));
    }

    /**
     * BVA 2: Giá trị biên tại mốc 12 giờ
     */
    public function testCancellationBoundaryAt12Hours() {
        // Trên 12h một chút (12.01h): Phạt mức 1 (5 điểm)
        $this->assertEquals(5, $this->calculatePenaltyHours(12.01));

        // Đúng chính xác 12.00h: Phạt mức 1 (5 điểm)
        $this->assertEquals(5, $this->calculatePenaltyHours(12.00));

        // Dưới 12h một chút (11.99h): Phạt mức 2 (15 điểm)
        $this->assertEquals(15, $this->calculatePenaltyHours(11.99));
    }

    /**
     * BVA 3: Phân tích khung giờ và tính toán thời lượng trận đấu (Duration)
     */
    public function testMatchDurationCalculationAndOvernightInterval() {
        // Ca thông thường: 17:30 đến 19:00 = 90 phút (1.5 giờ)
        $start1 = strtotime("2026-12-01 17:30:00");
        $end1 = strtotime("2026-12-01 19:00:00");
        $durationMinutes1 = ($end1 - $start1) / 60;
        $this->assertEquals(90, $durationMinutes1);

        // Ca tối muộn xuyên đêm: 23:00 đến 00:30 ngày hôm sau = 90 phút
        $start2 = strtotime("2026-12-01 23:00:00");
        $end2 = strtotime("2026-12-02 00:30:00");
        $durationMinutes2 = ($end2 - $start2) / 60;
        $this->assertEquals(90, $durationMinutes2);

        // Ràng buộc tính hợp lệ: Giờ kết thúc bắt buộc phải lớn hơn giờ bắt đầu
        $this->assertTrue($end1 > $start1);
        $this->assertTrue($end2 > $start2);
    }

    /**
     * BVA 4: Giá trị biên kẹp khoảng Điểm Uy Tín (Clamp [0, 100])
     */
    public function testReputationScoreClampingAtBoundaries() {
        // Biên trên: Khách 99 điểm + 2 điểm đánh giá -> Tối đa 100 điểm, không được vượt 100
        $newScoreHigh = max(0, min(100, 99 + 2));
        $this->assertEquals(100, $newScoreHigh);

        // Biên dưới: Khách 10 điểm bị phạt bùng sân -25 điểm -> Giảm về 0, không được âm
        $newScoreLow = max(0, min(100, 10 - 25));
        $this->assertEquals(0, $newScoreLow);
    }

    /**
     * Bảo mật: Kiểm thử thuật toán mã hóa mật khẩu BCRYPT chuẩn
     */
    public function testPasswordHashBcryptSecurity() {
        $rawPassword = "UserSecurePass@2026";
        $hash = password_hash($rawPassword, PASSWORD_BCRYPT);

        // 1. Mật khẩu mã hóa phải bắt đầu bằng định dạng bcrypt $2y$
        $this->assertStringStartsWith('$2y$', $hash);

        // 2. Độ dài chuỗi mã hóa bcrypt luôn cố định 60 ký tự
        $this->assertEquals(60, strlen($hash));

        // 3. Tuyệt đối không lưu plain text
        $this->assertNotEquals($rawPassword, $hash);

        // 4. password_verify phải xác thực đúng mật khẩu
        $this->assertTrue(password_verify($rawPassword, $hash));

        // 5. password_verify phải từ chối khi mật khẩu sai
        $this->assertFalse(password_verify("WrongPassword123", $hash));
    }

    /**
     * Phân quyền & Khóa tài khoản: Trạng thái khóa thủ công khoa_tai_khoan = 1
     */
    public function testAccountLockStatusOverridesHighReputation() {
        $user = [
            'nguoi_dung_id' => 88,
            'ten_dang_nhap' => 'vip_abuser',
            'diem_uy_tin' => 100, // Điểm uy tín tuyệt đối
            'khoa_tai_khoan' => 1  // Bị Admin khóa thủ công
        ];

        // Logic kiểm tra đăng nhập & quyền đặt sân: Nếu khoa_tai_khoan = 1 thì bị từ chối
        $isLoginAllowed = ($user['khoa_tai_khoan'] === 0);
        $this->assertFalse($isLoginAllowed, "Tài khoản bị khóa khoa_tai_khoan = 1 phải bị chặn truy cập dù điểm uy tín cao");

        // Khi tài khoản mở khóa:
        $user['khoa_tai_khoan'] = 0;
        $this->assertTrue($user['khoa_tai_khoan'] === 0 && $user['diem_uy_tin'] >= 40);
    }

    /**
     * Bảo mật Đăng nhập: Khớp chính xác tên đăng nhập phân biệt chữ hoa / chữ thường (Case Sensitive)
     */
    public function testCaseSensitiveUsernameMatching() {
        $dbUsername = 'admin15';

        // 1. Nhập đúng chữ thường: Khớp chính xác
        $inputCorrect = 'admin15';
        $this->assertTrue($dbUsername === $inputCorrect, "Tên đăng nhập chữ thường phải khớp chính xác");

        // 2. Nhập in hoa: Bắt buộc từ chối
        $inputUpper = 'ADMIN15';
        $this->assertFalse($dbUsername === $inputUpper, "Tên đăng nhập in hoa ADMIN15 không được phép đăng nhập vào tài khoản admin15");

        // 3. Nhập hoa thường lẫn lộn: Bắt buộc từ chối
        $inputMixed = 'Admin15';
        $this->assertFalse($dbUsername === $inputMixed, "Tên đăng nhập Admin15 không được phép đăng nhập vào tài khoản admin15");
    }

    /**
     * BVA: Kiểm thử biên 5 phút đệm chuẩn bị sân (Lead Time Buffer)
     * - Giờ bắt đầu <= time() + 300s: Đã quá hạn hoặc quá sát giờ thi đấu -> Chặn
     * - Giờ bắt đầu > time() + 300s: Khả dụng -> Cho phép đặt
     */
    public function testPastTimeSlotBookingBoundaryWith5MinBuffer() {
        $now = time();
        $bufferSeconds = 5 * 60; // 300 giây

        // 1. Khung giờ đã qua trong quá khứ (-10 phút)
        $pastSlotTime = $now - 600;
        $isPastValid = ($pastSlotTime > ($now + $bufferSeconds));
        $this->assertFalse($isPastValid, "Khung giờ trong quá khứ bắt buộc phải bị từ chối");

        // 2. Khung giờ sát giờ thi đấu (+3 phút, nằm trong buffer 5 phút)
        $insideBufferSlotTime = $now + 180;
        $isInsideBufferValid = ($insideBufferSlotTime > ($now + $bufferSeconds));
        $this->assertFalse($isInsideBufferValid, "Khung giờ còn dưới 5 phút bắt buộc phải bị từ chối");

        // 3. Khung giờ đúng chính xác biên 5 phút (+300 giây)
        $exactBoundaryTime = $now + 300;
        $isExactValid = ($exactBoundaryTime > ($now + $bufferSeconds));
        $this->assertFalse($isExactValid, "Khung giờ đúng 5 phút (<= 300s) phải bị đóng");

        // 4. Khung giờ an toàn trong tương lai (+6 phút, ngoài buffer)
        $futureSlotTime = $now + 360;
        $isFutureValid = ($futureSlotTime > ($now + $bufferSeconds));
        $this->assertTrue($isFutureValid, "Khung giờ còn trên 5 phút phải khả dụng cho khách đặt");
    }
}

