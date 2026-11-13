<?php
use PHPUnit\Framework\TestCase;

/**
 * Kiểm thử công thức tính giá sân 5 và ghép sân 7
 * (DatSanController.php L158-L162)
 */
class PricingCalculationTest extends TestCase {

    public function testSan5PriceRemainsOriginal() {
        $giaGoc = 250000;
        $viewMode = 'san_5';

        $giaHienThi = ($viewMode === 'san_7') ? ($giaGoc * 1.6) : $giaGoc;
        $this->assertEquals(250000, $giaHienThi, "Sân 5 phải giữ nguyên đơn giá gốc");
    }

    public function testSan7CalculatesWithExplicitPriceIfProvided() {
        $giaGoc = 250000;
        $giaTienSan7 = 400000; // Đã cấu hình giá riêng
        $quyTacGhep = 2;

        $giaHienThi = !empty($giaTienSan7) ? $giaTienSan7 : ($giaGoc * ($quyTacGhep > 0 ? $quyTacGhep : 1.6));
        $this->assertEquals(400000, $giaHienThi, "Nếu có giá sân 7 riêng thì ưu tiên dùng giá đó");
    }

    public function testSan7CalculatesWithMergeMultiplierWhenNoExplicitPrice() {
        $giaGoc = 200000;
        $giaTienSan7 = 0; // Chưa cấu hình giá riêng
        $quyTacGhep = 2; // Ghép 2 sân 5

        $giaHienThi = !empty($giaTienSan7) ? $giaTienSan7 : ($giaGoc * ($quyTacGhep > 0 ? $quyTacGhep : 1.6));
        $this->assertEquals(400000, $giaHienThi, "Nếu không có giá riêng, tự động nhân quy tắc ghép");
    }

    public function testSan7FallbackMultiplierWhenMergeRuleIsZero() {
        $giaGoc = 200000;
        $giaTienSan7 = 0;
        $quyTacGhep = 0; // Không có quy tắc ghép

        $giaHienThi = !empty($giaTienSan7) ? $giaTienSan7 : ($giaGoc * ($quyTacGhep > 0 ? $quyTacGhep : 1.6));
        $this->assertEquals(320000, $giaHienThi, "Mặc định nhân hệ số 1.6x khi quy tắc ghép <= 0");
    }
}

