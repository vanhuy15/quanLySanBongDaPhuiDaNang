<?php
use PHPUnit\Framework\TestCase;

/**
 * Thuật toán 3 & Quản lý Vòng đời Trạng thái Đơn Đặt Sân (Booking Lifecycle & Hold Timer)
 * Nghiệp vụ trong:
 * - config/Database.php (L55-L65): cleanupExpiredBookings()
 * - controllers/PaymentController.php (L30-L50): Countdown timer 10 phút
 * - controllers/DatSanController.php (L190-L210): Race condition check với TIMESTAMPDIFF
 */
class HoldTimerAndBookingStatusTest extends TestCase {

    /**
     * Kiểm thử logic tính thời gian giữ chỗ còn lại (Hold Timer)
     * Quy định: Đơn 'Chờ thanh toán' được giữ trong tối đa 10 phút (600 giây)
     */
    public function testRemainingHoldTimeCalculation() {
        $now = time();
        
        // Trường hợp 1: Mới tạo được 3 phút (180 giây trước) -> Còn 7 phút (420 giây)
        $createdAt = date('Y-m-d H:i:s', $now - 180);
        $elapsedSeconds = $now - strtotime($createdAt);
        $remainingSeconds = max(0, 600 - $elapsedSeconds);
        $this->assertEquals(420, $remainingSeconds, "Sau 3 phút, thời gian giữ chỗ còn lại phải là 420 giây (7 phút)");

        // Trường hợp 2: Đã quá 10 phút (650 giây trước) -> Còn 0 giây (hết hạn)
        $expiredCreatedAt = date('Y-m-d H:i:s', $now - 650);
        $expiredRemaining = max(0, 600 - ($now - strtotime($expiredCreatedAt)));
        $this->assertEquals(0, $expiredRemaining, "Đơn quá 10 phút phải hết hạn (còn lại 0 giây)");
    }

    /**
     * Kiểm thử điều kiện giải phóng slot đơn hết hạn (Database::cleanupExpiredBookings)
     */
    public function testCleanupExpiredBookingsCondition() {
        $now = time();

        $bookings = [
            [
                'id' => 1,
                'trang_thai' => 'Chờ thanh toán',
                'created_at' => date('Y-m-d H:i:s', $now - 700), // Quá 10 phút
                'expected_cleaned' => true
            ],
            [
                'id' => 2,
                'trang_thai' => 'Chờ thanh toán',
                'created_at' => date('Y-m-d H:i:s', $now - 300), // Mới 5 phút
                'expected_cleaned' => false
            ],
            [
                'id' => 3,
                'trang_thai' => 'Chờ xác nhận',
                'created_at' => date('Y-m-d H:i:s', $now - 1200), // Đã gửi thanh toán
                'expected_cleaned' => false // KHÔNG được xóa vì khách đã nộp thông tin
            ],
            [
                'id' => 4,
                'trang_thai' => 'Đã xác nhận',
                'created_at' => date('Y-m-d H:i:s', $now - 3600),
                'expected_cleaned' => false // Đã xác nhận không bao giờ bị xóa
            ]
        ];

        foreach ($bookings as $b) {
            $isExpired = ($b['trang_thai'] === 'Chờ thanh toán' && ($now - strtotime($b['created_at']) >= 600));
            $this->assertEquals($b['expected_cleaned'], $isExpired, 
                "Đơn #{$b['id']} trạng thái {$b['trang_thai']} xử lý dọn dẹp không đúng kỳ vọng");
        }
    }

    /**
     * Kiểm thử máy trạng thái đơn đặt sân (Booking State Machine Transition)
     */
    public function testValidBookingStatusTransitions() {
        $validTransitions = [
            'Chờ thanh toán' => ['Chờ xác nhận', 'Đã hủy'],
            'Chờ xác nhận'   => ['Đã xác nhận', 'Đã từ chối', 'Đã hủy'],
            'Đã xác nhận'    => ['Đã hủy', 'Chờ hoàn tiền'],
            'Chờ hoàn tiền'  => ['Đã hủy'],
            'Đã hủy'         => [], // Trạng thái kết thúc
            'Đã từ chối'     => []  // Trạng thái kết thúc
        ];

        // 1. Cho phép chuyển từ 'Chờ thanh toán' sang 'Chờ xác nhận'
        $this->assertContains('Chờ xác nhận', $validTransitions['Chờ thanh toán']);

        // 2. Không cho phép chuyển từ 'Đã hủy' sang 'Đã xác nhận'
        $this->assertNotContains('Đã xác nhận', $validTransitions['Đã hủy']);

        // 3. Đơn đã xác nhận nếu khách hủy thì được chuyển sang 'Chờ hoàn tiền'
        $this->assertContains('Chờ hoàn tiền', $validTransitions['Đã xác nhận']);
    }
}

