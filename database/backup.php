<?php
/**
 * DATABASE BACKUP CLI UTILITY
 * Usage: php database/backup.php
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Logger.php';

use Config\Database;
use Core\Logger;

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

$date = date('Ymd_His');
$backupFile = "{$backupDir}/backup_{$date}.sql";

$conn = Database::getConnection();
$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    $tables[] = $row[0];
}

$output = "-- ======================================================\n";
$output .= "-- DATABASE BACKUP: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- ======================================================\n\n";
$output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    // Không backup logs tạm
    if ($table === 'audit_logs' || $table === 'migrations') continue;

    $createRes = $conn->query("SHOW CREATE TABLE `{$table}`");
    $createRow = $createRes->fetch_row();
    $output .= "-- Structure for table `{$table}`\n";
    $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $output .= $createRow[1] . ";\n\n";

    $dataRes = $conn->query("SELECT * FROM `{$table}`");
    if ($dataRes->num_rows > 0) {
        $output .= "-- Dumping data for table `{$table}`\n";
        while ($row = $dataRes->fetch_assoc()) {
            $keys = array_map(function($k) { return "`{$k}`"; }, array_keys($row));
            $values = array_map(function($v) use ($conn) {
                if ($v === null) return "NULL";
                return "'" . $conn->real_escape_string($v) . "'";
            }, array_values($row));

            $output .= "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        $output .= "\n";
    }
}

$output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($backupFile, $output);
$sizeKb = round(filesize($backupFile) / 1024, 2);

echo "=======================================================\n";
echo "  DATABASE BACKUP COMPLETED\n";
echo "  Tập tin: {$backupFile}\n";
echo "  Dung lượng: {$sizeKb} KB\n";
echo "=======================================================\n";

Logger::info("Database backup created: {$backupFile} ({$sizeKb} KB)");

