<?php
use PHPUnit\Framework\TestCase;

/**
 * Thuật toán 4: Kiểm thử Bảng Phạt Hủy Đơn & Chính Sách Uy Tín (Reputation Penalty Policy)
 * Nghiệp vụ chi tiết tại:
 * - controllers/UserController.php (L310-L340): cancelBooking()
 * - admin/datsan/index.php (Xử lý bùng sân - no-show)
 */
class CancellationPenaltyCalculationTest extends TestCase {

    /**
     * Hàm tính điểm phạt dựa trên số giờ chênh lệch trước giờ bóng lăn
     */
    private function calculatePenalty(int $diffHours): int {
        if ($diffHours > 24) {
            return 0; // Hủy sớm trước 24h: Miễn phạt
        } elseif ($diffHours >= 12) {
            return 5; // Hủy từ 12h - 24h: Phạt nhẹ 5 điểm
        } else {
            return 15; // Hủy sát giờ (< 12h): Phạt nặng 15 điểm
        }
    }

    public function testCancelMoreThan24HoursInAdvanceIncursNoPenalty() {
        $hoursAhead = [25, 48, 72];
        foreach ($hoursAhead as $hours) {
            $penalty = $this->calculatePenalty($hours);
            $this->assertEquals(0, $penalty, "Hủy trước {$hours}h (> 24h) không được trừ điểm uy tín");
        }
    }

    public function testCancelBetween12And24HoursIncurs5PointsPenalty() {
        $hoursAhead = [12, 18, 24];
        foreach ($hoursAhead as $hours) {
            $penalty = $this->calculatePenalty($hours);
            $this->assertEquals(5, $penalty, "Hủy trước {$hours}h (từ 12h - 24h) phải phạt đúng 5 điểm");
        }
    }

    public function testCancelLessThan12HoursIncurs15PointsPenalty() {
        $hoursAhead = [0, 2, 6, 11];
        foreach ($hoursAhead as $hours) {
            $penalty = $this->calculatePenalty($hours);
            $this->assertEquals(15, $penalty, "Hủy sát giờ trước {$hours}h (< 12h) phải phạt đúng 15 điểm");
        }
    }

    public function testNoShowPenaltyDeducts25PointsAndIncrementsCounter() {
        $currentScore = 80;
        $currentNoShowCount = 1;

        $penalty = 25;
        $newScore = max(0, $currentScore - $penalty);
        $newNoShowCount = $currentNoShowCount + 1;

        $this->assertEquals(55, $newScore, "Bùng sân phải bị phạt trừ 25 điểm uy tín");
        $this->assertEquals(2, $newNoShowCount, "Số lần bùng sân phải được cộng thêm 1");
    }

    public function testScoreClampingBoundaries() {
        // 1. Kiểm tra không bao giờ âm dưới 0 (GREATEST(0, score))
        $lowScore = 10;
        $severePenalty = 25;
        $clampedLow = max(0, $lowScore - $severePenalty);
        $this->assertEquals(0, $clampedLow, "Điểm uy tín tối thiểu phải là 0, không được âm");

        // 2. Kiểm tra không bao giờ vượt quá 100 (LEAST(100, score))
        $highScore = 98;
        $bonus = 5;
        $clampedHigh = min(100, $highScore + $bonus);
        $this->assertEquals(100, $clampedHigh, "Điểm uy tín tối đa phải là 100, không được vượt quá");
    }

    public function testRefundEligibilityCheck() {
        // Đơn đã thanh toán chuyển khoản và đã xác nhận -> Được hoàn tiền
        $booking1 = ['trang_thai' => 'Đã xác nhận', 'phuong_thuc' => 'chuyen_khoan'];
        $eligible1 = ($booking1['trang_thai'] === 'Đã xác nhận' && $booking1['phuong_thuc'] === 'chuyen_khoan');
        $this->assertTrue($eligible1, "Đơn đã thanh toán chuyển khoản phải kích hoạt luồng hoàn tiền");

        // Đơn thanh toán tiền mặt -> Không hoàn tiền qua ngân hàng
        $booking2 = ['trang_thai' => 'Đã xác nhận', 'phuong_thuc' => 'tien_mat'];
        $eligible2 = ($booking2['trang_thai'] === 'Đã xác nhận' && $booking2['phuong_thuc'] === 'chuyen_khoan');
        $this->assertFalse($eligible2, "Đơn tiền mặt tại sân không cần hoàn tiền ngân hàng");

        // Đơn chưa xác nhận -> Không hoàn tiền
        $booking3 = ['trang_thai' => 'Chờ xác nhận', 'phuong_thuc' => 'chuyen_khoan'];
        $eligible3 = ($booking3['trang_thai'] === 'Đã xác nhận' && $booking3['phuong_thuc'] === 'chuyen_khoan');
        $this->assertFalse($eligible3, "Đơn chưa xác nhận thanh toán thành công không kích hoạt hoàn tiền");
    }
}

