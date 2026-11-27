@echo off
setlocal
cd /d "%~dp0"
title Smart QR Menu - Backend

if not exist ".env" (
    echo [ERROR] backend\.env not found. Copy .env.example to .env, then set DB_PASSWORD and ADMIN_PASSWORD.
    pause
    exit /b 1
)

if not exist "vendor\autoload.php" (
    echo Installing PHP dependencies...
    call composer install
    if errorlevel 1 ( pause & exit /b 1 )
)

if not exist "public\storage" (
    echo Creating storage link...
    php artisan storage:link
)

echo Running migrations...
php artisan migrate --force
if errorlevel 1 ( pause & exit /b 1 )

echo Starting API on http://localhost:8000
php artisan serve --host=127.0.0.1 --port=8000
pause
