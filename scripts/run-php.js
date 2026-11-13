#!/usr/bin/env node
/**
 * PORTABLE PHP RUNNER (scripts/run-php.js)
 * Automatically detects PHP executable across Linux, macOS, and Windows environments.
 * Supports CI/CD environments (GitHub Actions) without hardcoding local disk paths.
 */

const { spawnSync } = require('child_process');
const fs = require('fs');

function findPhpBinary() {
    // 1. Kiểm tra biến môi trường người dùng chỉ định
    if (process.env.PHP_PATH && fs.existsSync(process.env.PHP_PATH)) {
        return process.env.PHP_PATH;
    }

    // 2. Thử lệnh 'php' có sẵn trên PATH (Linux, Mac, Windows đã add PATH)
    try {
        const check = spawnSync('php', ['-v'], { stdio: 'ignore' });
        if (check.status === 0) {
            return 'php';
        }
    } catch (e) {
        // Tiếp tục kiểm tra các fallback
    }

    // 3. Fallback cho các môi trường Windows XAMPP phổ biến
    const windowsFallbacks = [
        'D:\\XAMPP\\php\\php.exe',
        'C:\\xampp\\php\\php.exe',
        'C:\\XAMPP\\php\\php.exe',
        'D:\\xampp\\php\\php.exe'
    ];

    for (const p of windowsFallbacks) {
        if (fs.existsSync(p)) {
            return p;
        }
    }

    // Mặc định trả về 'php' để hệ thống báo lỗi nếu không tìm thấy
    return 'php';
}

const phpBin = findPhpBinary();
const args = process.argv.slice(2);

const proc = spawnSync(phpBin, args, {
    stdio: 'inherit',
    shell: false
});

if (proc.error) {
    console.error(`[Error] Không thể khởi chạy PHP (${phpBin}):`, proc.error.message);
    process.exit(1);
}

process.exit(proc.status ?? 0);

