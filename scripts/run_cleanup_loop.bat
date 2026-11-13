@echo off
title PitchManager - Slot Auto-Release Daemon
echo =======================================================
echo   PITCHMANAGER - BACKGROUND SLOT RELEASE WORKER
echo   Dang chay dinh ky moi 2 phut (120 giay)...
echo   Nhan Ctrl+C de dung tien trinh.
echo =======================================================

:loop
node "%~dp0run-php.js" "%~dp0cron_cleanup_slots.php"
echo [Timer] Dang cho 120 giay cho lan quet tiep theo...
timeout /t 120 /nobreak >nul
goto loop
