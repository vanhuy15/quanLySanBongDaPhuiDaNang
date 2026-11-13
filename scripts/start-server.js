#!/usr/bin/env node
/**
 * PORTABLE DEV SERVER STARTER (scripts/start-server.js)
 * Tự động phát hiện Apache (XAMPP trên D: hoặc C:) hoặc chạy PHP Built-in Server.
 * Khởi chạy trình duyệt mở ứng dụng.
 */

const { execSync, spawn } = require('child_process');
const fs = require('fs');

const apachePaths = [
    process.env.APACHE_PATH,
    'D:\\XAMPP\\apache\\bin\\httpd.exe',
    'C:\\xampp\\apache\\bin\\httpd.exe',
    'C:\\XAMPP\\apache\\bin\\httpd.exe',
    'D:\\xampp\\apache\\bin\\httpd.exe'
].filter(Boolean);

let foundApache = null;
for (const p of apachePaths) {
    if (fs.existsSync(p)) {
        foundApache = p;
        break;
    }
}

if (foundApache) {
    console.log(`[Server] Khởi động Apache XAMPP: ${foundApache}`);
    try {
        if (process.platform === 'win32') {
            execSync(`powershell -Command "Start-Process '${foundApache}' -WindowStyle Hidden"`);
            execSync('powershell -Command "Start-Process \'http://localhost/quanlysanbong/\'"');
        } else {
            spawn(foundApache, [], { detached: true, stdio: 'ignore' }).unref();
        }
        console.log('[Server] Đã mở hệ thống tại: http://localhost/quanlysanbong/');
    } catch (e) {
        console.error('[Error] Không thể khởi động Apache:', e.message);
    }
} else {
    console.log('[Server] Không tìm thấy Apache XAMPP, chuyển sang PHP Built-in Server trên cổng 8000...');
    const phpRunner = require('path').join(__dirname, 'run-php.js');
    const server = spawn('node', [phpRunner, '-S', 'localhost:8000'], {
        stdio: 'inherit',
        shell: false
    });
    console.log('[Server] Ứng dụng sẵn sàng tại: http://localhost:8000/');
}
