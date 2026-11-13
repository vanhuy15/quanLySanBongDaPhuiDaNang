<?php
/**
 * DATABASE SEEDER UTILITY
 * Usage: php database/seed.php
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Logger.php';

use Config\Database;
use Core\Logger;

echo "=======================================================\n";
echo "  DATABASE SEEDER\n";
echo "=======================================================\n\n";

$conn = Database::getConnection();

// Lấy thông tin từ biến môi trường hoặc dùng tài khoản demo chuẩn
$adminUser = getenv('SEED_ADMIN_USER') ?: 'admin15';
$adminPass = getenv('SEED_ADMIN_PASS') ?: '123456';
$adminEmail = getenv('SEED_ADMIN_EMAIL') ?: 'admin@example.com';

$testUser = getenv('SEED_TEST_USER') ?: 'testuser';
$testPass = getenv('SEED_TEST_PASS') ?: '123456';
$testEmail = getenv('SEED_TEST_EMAIL') ?: 'testuser@example.com';

// 1. Tạo hoặc cập nhật tài khoản Admin
$adminHash = password_hash($adminPass, PASSWORD_BCRYPT);
$checkAdmin = $conn->prepare("SELECT nguoi_dung_id FROM nguoi_dung WHERE ten_dang_nhap = ?");
$checkAdmin->bind_param("s", $adminUser);
$checkAdmin->execute();
$adminExists = $checkAdmin->get_result()->fetch_assoc();
$checkAdmin->close();

if (!$adminExists) {
    $ins = $conn->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, email, sdt, vai_tro, diem_uy_tin) VALUES (?, ?, 'Quản Trị Viên', ?, '0905123456', 'admin', 100)");
    $ins->bind_param("sss", $adminUser, $adminHash, $adminEmail);
    $ins->execute();
    $ins->close();
    echo "Đã tạo tài khoản Admin: {$adminUser}\n";
} else {
    echo "Tài khoản Admin đã tồn tại: {$adminUser}\n";
}

// 2. Tạo hoặc cập nhật tài khoản Khách hàng test
$testHash = password_hash($testPass, PASSWORD_BCRYPT);
$checkTest = $conn->prepare("SELECT nguoi_dung_id FROM nguoi_dung WHERE ten_dang_nhap = ?");
$checkTest->bind_param("s", $testUser);
$checkTest->execute();
$testExists = $checkTest->get_result()->fetch_assoc();
$checkTest->close();

if (!$testExists) {
    $ins2 = $conn->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, email, sdt, vai_tro, diem_uy_tin) VALUES (?, ?, 'Người Dùng Kiểm Thử', ?, '0912345678', 'khach_hang', 100)");
    $ins2->bind_param("sss", $testUser, $testHash, $testEmail);
    $ins2->execute();
    $ins2->close();
    echo "Đã tạo tài khoản Khách hàng: {$testUser}\n";
} else {
    echo "Tài khoản Khách hàng đã tồn tại: {$testUser}\n";
}

echo "\n=======================================================\n";
echo "  HOÀN TẤT KHỞI TẠO DỮ LIỆU SEED!\n";
echo "=======================================================\n";
Logger::info("Database seeded successfully.");
