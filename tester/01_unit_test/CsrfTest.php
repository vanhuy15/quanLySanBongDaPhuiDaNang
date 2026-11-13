<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/csrf.php';

class CsrfTest extends TestCase {

    protected function setUp(): void {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION = [];
    }

    public function testGenerateCsrfTokenCreatesValidToken() {
        $token = generate_csrf_token();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token), "CSRF Token phải có độ dài 64 ký tự hex (32 bytes)");
        $this->assertEquals($_SESSION['csrf_token'], $token, "Token phải được lưu vào session");
    }

    public function testGenerateCsrfTokenReusesExistingToken() {
        $firstToken = generate_csrf_token();
        $secondToken = generate_csrf_token();
        $this->assertEquals($firstToken, $secondToken, "Cùng một phiên phải dùng chung token nếu chưa hết hạn");
    }

    public function testVerifyCsrfTokenReturnsTrueForValidToken() {
        $token = generate_csrf_token();
        $this->assertTrue(verify_csrf_token($token), "Xác thực token hợp lệ phải trả về true");
    }

    public function testVerifyCsrfTokenReturnsFalseForTamperedToken() {
        generate_csrf_token();
        $fakeToken = "fake_csrf_token_1234567890abcdef1234567890abcdef";
        $this->assertFalse(verify_csrf_token($fakeToken), "Xác thực token giả mạo phải trả về false");
    }

    public function testVerifyCsrfTokenReturnsFalseForEmptyToken() {
        generate_csrf_token();
        $this->assertFalse(verify_csrf_token(""), "Token rỗng phải trả về false");
        $this->assertFalse(verify_csrf_token(null), "Token null phải trả về false");
    }

    public function testCsrfFieldContainsInputTagWithToken() {
        $field = csrf_field();
        $this->assertStringContainsString('<input type="hidden" name="csrf_token"', $field);
        $this->assertStringContainsString($_SESSION['csrf_token'], $field);
    }
}
