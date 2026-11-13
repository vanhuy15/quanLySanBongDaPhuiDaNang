#!/usr/bin/env node
/**
 * STOP DEV SERVER (scripts/stop-server.js)
 */

const { execSync } = require('child_process');

try {
    if (process.platform === 'win32') {
        execSync('powershell -Command "Stop-Process -Name httpd -Force -ErrorAction SilentlyContinue"');
    } else {
        execSync('pkill httpd || true');
    }
    console.log('[Server] Đã dừng server Apache httpd.');
} catch (e) {
    // Silently continue
}

