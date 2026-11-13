<?php
/**
 * DATABASE RESTORE CLI UTILITY
 * Usage: php database/restore.php [path_to_sql_file]
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Logger.php';

use Config\Database;
use Core\Logger;

$file = $argv[1] ?? null;

if (!$file) {
    // Tìm file backup mới nhất trong database/backups
    $backups = glob(__DIR__ . '/backups/*.sql');
    if (empty($backups)) {
        echo "Lỗi: Không tìm thấy file backup nào trong database/backups/\n";
        echo "Cú pháp: php database/restore.php <duong_dan_file.sql>\n";
        exit(1);
    }
    rsort($backups);
    $file = $backups[0];
    echo "Sử dụng bản backup mới nhất: " . basename($file) . "\n";
}

if (!file_exists($file)) {
    echo "Lỗi: Tập tin không tồn tại: {$file}\n";
    exit(1);
}

echo "Đang khôi phục dữ liệu từ: {$file}...\n";
$conn = Database::getConnection();

$sql = file_get_contents($file);
$conn->multi_query($sql);

do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->errno) {
    echo "LỖI: " . $conn->error . "\n";
    Logger::error("Database restore failed: {$file}", ['error' => $conn->error]);
    exit(1);
}

echo "=======================================================\n";
echo "  KHÔI PHỤC CƠ SỞ DỮ LIỆU THÀNH CÔNG!\n";
echo "=======================================================\n";
Logger::info("Database restored from: {$file}");
