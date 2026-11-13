<?php
/**
 * DATABASE MIGRATION RUNNER
 * Usage: php database/migrate.php
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Logger.php';

use Config\Database;
use Core\Logger;

echo "=======================================================\n";
echo "  DATABASE MIGRATION RUNNER\n";
echo "=======================================================\n\n";

$conn = Database::getConnection();

// 1. Tạo bảng quản lý migrations nếu chưa có
$conn->query("
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 2. Lấy danh sách migration đã chạy
$executed = [];
$res = $conn->query("SELECT migration FROM migrations");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $executed[] = $row['migration'];
    }
}

// 3. Quét thư mục migrations
$migrationDir = __DIR__ . '/migrations';
if (!is_dir($migrationDir)) {
    echo "Thư mục migrations không tồn tại.\n";
    exit(0);
}

$files = glob($migrationDir . '/*.sql');
sort($files);

$runCount = 0;
foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $executed)) {
        continue;
    }

    echo "Đang thực thi migration: {$filename}... ";
    $sqlContent = file_get_contents($file);

    // Tách các câu lệnh theo dấu chấm phẩy
    $queries = array_filter(array_map('trim', explode(';', $sqlContent)));

    $conn->begin_transaction();
    try {
        foreach ($queries as $query) {
            if (!empty($query)) {
                if (!$conn->query($query)) {
                    throw new \Exception($conn->error . "\nTrong truy vấn: " . substr($query, 0, 100));
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->bind_param("s", $filename);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo "OK\n";
        Logger::info("Database migration executed successfully: {$filename}");
        $runCount++;
    } catch (\Exception $e) {
        $conn->rollback();
        echo "LỖI!\n";
        echo "Chi tiết: " . $e->getMessage() . "\n";
        Logger::error("Migration failed: {$filename}", ['error' => $e->getMessage()]);
        exit(1);
    }
}

if ($runCount === 0) {
    echo "Cơ sở dữ liệu đã ở phiên bản mới nhất (Không có migration mới).\n";
} else {
    echo "\nHoàn tất thực thi {$runCount} migrations thành công!\n";
}
