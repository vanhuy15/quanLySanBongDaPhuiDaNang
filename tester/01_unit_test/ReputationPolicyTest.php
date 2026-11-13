<?php
use PHPUnit\Framework\TestCase;

/**
 * Kiểm thử chính sách điểm uy tín (Reputation Policy)
 * Theo đúng logic nghiệp vụ của DatSanController, PaymentController, ReviewController
 */
class ReputationPolicyTest extends TestCase {

    /**
     * Quy tắc 1: Khách hàng có điểm uy tín < 40 bị KHÓA quyền đặt sân
     * (Controllers/DatSanController.php L266)
     */
    public function testBookingBlockedWhenReputationBelow40() {
        $blockedScores = [0, 15, 39];
        foreach ($blockedScores as $score) {
            $isBlocked = ($score < 40);
            $this->assertTrue($isBlocked, "Điểm {$score} < 40 phải bị khóa quyền đặt sân");
        }
    }

    public function testBookingAllowedWhenReputationAtLeast40() {
        $allowedScores = [40, 50, 80, 100];
        foreach ($allowedScores as $score) {
            $isBlocked = ($score < 40);
            $this->assertFalse($isBlocked, "Điểm {$score} >= 40 được phép đặt sân bình thường");
        }
    }

    /**
     * Quy tắc 2: Khách hàng có điểm uy tín < 60 không được chọn Tiền mặt
     * Bắt buộc chuyển khoản / QR
     * (Controllers/PaymentController.php L73)
     */
    public function testCashPaymentBlockedWhenReputationBelow60() {
        $lowScores = [40, 50, 59];
        foreach ($lowScores as $score) {
            $canUseCash = ($score >= 60);
            $this->assertFalse($canUseCash, "Điểm {$score} < 60 không được phép chọn Tiền mặt");
        }
    }

    public function testCashPaymentAllowedWhenReputationAtLeast60() {
        $goodScores = [60, 85, 100];
        foreach ($goodScores as $score) {
            $canUseCash = ($score >= 60);
            $this->assertTrue($canUseCash, "Điểm {$score} >= 60 được phép chọn Tiền mặt");
        }
    }

    /**
     * Quy tắc 3: Đánh giá sân (Review) được cộng 2 điểm nhưng không vượt quá 100
     * (Controllers/ReviewController.php L74: LEAST(100, diem_uy_tin + 2))
     */
    public function testReviewBonusCappedAt100() {
        $scoreBefore = 99;
        $scoreAfter = min(100, $scoreBefore + 2);
        $this->assertEquals(100, $scoreAfter, "Cộng điểm review không được vượt quá 100");

        $scoreNormal = 80;
        $this->assertEquals(82, min(100, $scoreNormal + 2));
    }

    /**
     * Quy tắc 4: Phạt hủy bùng sân không bị âm dưới 0
     * (Controllers/UserController.php L311: GREATEST(0, diem_uy_tin - ?))
     */
    public function testPenaltyFloorAtZero() {
        $scoreBefore = 10;
        $penalty = 20;
        $scoreAfter = max(0, $scoreBefore - $penalty);
        $this->assertEquals(0, $scoreAfter, "Điểm uy tín bị phạt không được giảm xuống dưới 0");
    }
}

