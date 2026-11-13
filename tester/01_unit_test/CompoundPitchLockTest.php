<?php
use PHPUnit\Framework\TestCase;

/**
 * Thuật toán 2: Kiểm thử Logic Ghép/Tách Sân Đa Năng (Compound Pitch Merging)
 * Chi tiết nghiệp vụ trong Controllers/DatSanController.php (L220-L260):
 * - Sân 7 được ghép từ 2 sân 5 con (Ví dụ: Sân con A và Sân con B).
 * - Khi đặt Sân 7, trường `san_con_khoa` lưu giá trị 'A,B'.
 * - Kiểm tra xung đột đa chiều:
 *   1. Đặt Sân con A/B khi Sân 7 đã có người đặt -> Bị chặn.
 *   2. Đặt Sân 7 khi Sân con A hoặc Sân con B đã có người đặt -> Bị chặn.
 *   3. Đặt Sân con C (độc lập) không ảnh hưởng tới cụm Sân 7 ghép (A,B).
 *   4. Khi hủy Sân 7 -> Cả 2 Sân con A và B cùng được giải phóng.
 */
class CompoundPitchLockTest extends TestCase {

    /**
     * Hàm mô phỏng logic kiểm tra xung đột sân ghép trong DatSanController
     */
    private function checkConflict(string $targetSanCon, ?string $targetSanConKhoa, array $existingBookings): bool {
        foreach ($existingBookings as $b) {
            $bookedSanCon = $b['ten_san_con'];
            $bookedKhoa = $b['san_con_khoa'] ?? null;

            // 1. Trùng chính xác tên sân con
            if ($targetSanCon === $bookedSanCon) {
                return true; // Xung đột
            }

            // 2. Nếu đơn hiện tại đặt Sân 7 (có san_con_khoa)
            if (!empty($targetSanConKhoa)) {
                $lockedList = array_map('trim', explode(',', $targetSanConKhoa));
                foreach ($lockedList as $part) {
                    if (stripos($bookedSanCon, $part) !== false) {
                        return true; // Xung đột vì sân con đơn lẻ đã bị đặt
                    }
                }
            }

            // 3. Nếu đơn đã đặt trước đó là Sân 7 (có san_con_khoa)
            if (!empty($bookedKhoa)) {
                $lockedList = array_map('trim', explode(',', $bookedKhoa));
                foreach ($lockedList as $part) {
                    if (stripos($targetSanCon, $part) !== false) {
                        return true; // Xung đột vì sân con nằm trong sân 7 đã được đặt
                    }
                }
            }
        }
        return false; // Không xung đột
    }

    public function testSan7BlockedWhenSanConAIsAlreadyBooked() {
        $existingBookings = [
            ['ten_san_con' => 'Sân con A', 'san_con_khoa' => null]
        ];

        // Khách muốn đặt Sân 7 (khóa A,B)
        $hasConflict = $this->checkConflict('Sân 7 (A+B)', 'A,B', $existingBookings);
        $this->assertTrue($hasConflict, "Không được phép đặt Sân 7 khi Sân con A đã có khách đặt");
    }

    public function testSan7BlockedWhenSanConBIsAlreadyBooked() {
        $existingBookings = [
            ['ten_san_con' => 'Sân con B', 'san_con_khoa' => null]
        ];

        $hasConflict = $this->checkConflict('Sân 7 (A+B)', 'A,B', $existingBookings);
        $this->assertTrue($hasConflict, "Không được phép đặt Sân 7 khi Sân con B đã có khách đặt");
    }

    public function testSanConABlockedWhenSan7IsAlreadyBooked() {
        $existingBookings = [
            ['ten_san_con' => 'Sân 7 (A+B)', 'san_con_khoa' => 'A,B']
        ];

        // Khách muốn đặt lẻ Sân con A
        $hasConflict = $this->checkConflict('Sân con A', null, $existingBookings);
        $this->assertTrue($hasConflict, "Không được phép đặt lẻ Sân con A khi Sân 7 ghép A+B đã có người đặt");
    }

    public function testSanConBBlockedWhenSan7IsAlreadyBooked() {
        $existingBookings = [
            ['ten_san_con' => 'Sân 7 (A+B)', 'san_con_khoa' => 'A,B']
        ];

        // Khách muốn đặt lẻ Sân con B
        $hasConflict = $this->checkConflict('Sân con B', null, $existingBookings);
        $this->assertTrue($hasConflict, "Không được phép đặt lẻ Sân con B khi Sân 7 ghép A+B đã có người đặt");
    }

    public function testSanConCNotBlockedWhenSan7IsBooked() {
        $existingBookings = [
            ['ten_san_con' => 'Sân 7 (A+B)', 'san_con_khoa' => 'A,B']
        ];

        // Khách muốn đặt Sân con C (sân độc lập, không nằm trong cụm ghép A,B)
        $hasConflict = $this->checkConflict('Sân con C', null, $existingBookings);
        $this->assertFalse($hasConflict, "Sân con C độc lập không được phép bị ảnh hưởng bởi Sân 7 (A+B)");
    }

    public function testSan7AllowedWhenBothSanAAndSanBAreFree() {
        $existingBookings = [
            ['ten_san_con' => 'Sân con C', 'san_con_khoa' => null] // Chỉ có Sân C có khách
        ];

        // Khách đặt Sân 7 (ghép A,B)
        $hasConflict = $this->checkConflict('Sân 7 (A+B)', 'A,B', $existingBookings);
        $this->assertFalse($hasConflict, "Sân 7 phải khả dụng khi cả 2 sân con A và B đều đang trống");
    }

    public function testCancellationOfSan7ReleasesBothSanAAndSanB() {
        // Mô phỏng trạng thái sau khi Sân 7 bị hủy (bị xóa hoặc trạng thái 'Đã hủy')
        $existingBookingsAfterCancel = [];

        $conflictA = $this->checkConflict('Sân con A', null, $existingBookingsAfterCancel);
        $conflictB = $this->checkConflict('Sân con B', null, $existingBookingsAfterCancel);

        $this->assertFalse($conflictA, "Sân con A phải được giải phóng ngay sau khi Sân 7 hủy");
        $this->assertFalse($conflictB, "Sân con B phải được giải phóng ngay sau khi Sân 7 hủy");
    }
}

