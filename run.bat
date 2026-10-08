@echo off
title JatraPoth Dev Server Launcher
cd /d "%~dp0"

echo ================================================
echo    Starting JatraPoth / BusBooking Dev Servers  
echo ================================================
echo.

echo [1/3] Starting Laravel backend (php artisan serve)...
start "Laravel Backend (php artisan serve)" cmd /k "php artisan serve"

echo [2/3] Starting Vite frontend (npm run dev)...
start "Vite Frontend (npm run dev)" cmd /k "npm run dev"

echo [3/3] Opening browser at http://127.0.0.1:8000...
timeout /t 2 /nobreak >nul
start http://127.0.0.1:8000

echo.
echo All servers started! You can close this launcher window.
echo App URL: http://127.0.0.1:8000
echo ================================================
timeout /t 3 >nul
exit
