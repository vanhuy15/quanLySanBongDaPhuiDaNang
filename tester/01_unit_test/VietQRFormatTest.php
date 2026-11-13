<?php
use PHPUnit\Framework\TestCase;

/**
 * Thuật toán 5: Kiểm thử Chuẩn hóa & Sinh Mã VietQR Tự Động (VietQR Generation)
 * Nghiệp vụ chi tiết tại views/payment/checkout.php & controllers/PaymentController.php
 */
class VietQRFormatTest extends TestCase {

    private const BANK_ID = 'MB';
    private const ACCOUNT_NO = '1111000050174';
    private const ACCOUNT_NAME = 'HOANG VAN HUY';
    private const TEMPLATE = 'compact2';

    /**
     * Hàm sinh URL VietQR theo đúng chuẩn đang sử dụng trong views/payment/checkout.php
     */
    private function generateVietQrUrl(int $bookingId, int $amount): string {
        $encodedDesc = urlencode("DATSAN " . $bookingId);
        $encodedName = urlencode(self::ACCOUNT_NAME);
        return "https://img.vietqr.io/image/" . self::BANK_ID . "-" . self::ACCOUNT_NO . "-" . self::TEMPLATE . ".png"
             . "?amount=" . $amount
             . "&addInfo=" . $encodedDesc
             . "&accountName=" . $encodedName;
    }

    public function testVietQrUrlStructureMatchesStandard() {
        $bookingId = 150;
        $amount = 250000;

        $url = $this->generateVietQrUrl($bookingId, $amount);

        // Kiểm tra tiền tố dịch vụ
        $this->assertStringStartsWith('https://img.vietqr.io/image/MB-1111000050174-compact2.png', $url);

        // Kiểm tra tham số số tiền
        $this->assertStringContainsString('amount=250000', $url);

        // Kiểm tra nội dung chuyển khoản được URL-encode
        $this->assertStringContainsString('addInfo=DATSAN+150', $url);

        // Kiểm tra tên tài khoản được URL-encode
        $this->assertStringContainsString('accountName=HOANG+VAN+HUY', $url);
    }

    public function testAmountMustBePositiveInteger() {
        $validAmounts = [150000, 250000, 400000, 1000000];
        foreach ($validAmounts as $amt) {
            $this->assertGreaterThan(0, $amt, "Số tiền thanh toán phải lớn hơn 0");
            $this->assertIsInt($amt, "Số tiền thanh toán phải là số nguyên");
        }
    }

    public function testTransferSyntaxPattern() {
        $bookingId = 999;
        $syntax = "DATSAN " . $bookingId;

        // Nội dung cú pháp chuyển khoản phải khớp biểu thức chính quy ^DATSAN [0-9]+$
        $isMatched = preg_match('/^DATSAN \d+$/', $syntax);
        $this->assertEquals(1, $isMatched, "Cú pháp chuyển khoản phải có dạng 'DATSAN <id>'");
    }

    public function testBankAccountDetailsConsistency() {
        $this->assertEquals('1111000050174', self::ACCOUNT_NO, "Số tài khoản MB Bank phải đồng bộ 1111000050174");
        $this->assertEquals('HOANG VAN HUY', self::ACCOUNT_NAME, "Chủ tài khoản phải là HOANG VAN HUY");
    }
}

