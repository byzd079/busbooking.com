# BusBooking Dev Server Launcher
# Run Laravel backend and Vite frontend together

$host.ui.RawUI.WindowTitle = "BusBooking Dev Launcher"
$projectRoot = $PSScriptRoot

Clear-Host
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "       JatraPoth / BusBooking Dev Servers       " -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[1/3] Starting Laravel backend (php artisan serve)..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "`$host.ui.RawUI.WindowTitle='Laravel - php artisan serve'; cd '$projectRoot'; php artisan serve"

Write-Host "[2/3] Starting Vite frontend (npm run dev)..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "`$host.ui.RawUI.WindowTitle='Vite - npm run dev'; cd '$projectRoot'; npm run dev"

Write-Host "[3/3] Opening browser at http://127.0.0.1:8000..." -ForegroundColor Yellow
Start-Sleep -Seconds 2
Start-Process "http://127.0.0.1:8000"

Write-Host ""
Write-Host "All dev servers are running!" -ForegroundColor Green
Write-Host "App URL: http://127.0.0.1:8000" -ForegroundColor Cyan
Write-Host "Admin Login: http://127.0.0.1:8000/admin_login" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
