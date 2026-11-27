@echo off
rem Starts backend (http://localhost:8000) and frontend (http://localhost:5173) in separate windows.
rem Usage: run.bat            -> start both
rem        run.bat backend    -> internal: backend only
rem        run.bat frontend   -> internal: frontend only
setlocal
cd /d "%~dp0"

if /i "%~1"=="backend" goto backend
if /i "%~1"=="frontend" goto frontend

start "Smart QR Menu - Backend" cmd /k ""%~f0" backend"
start "Smart QR Menu - Frontend" cmd /k ""%~f0" frontend"
exit /b 0

:backend
title Smart QR Menu - Backend
cd /d "%~dp0backend"

if not exist ".env" (
    echo [ERROR] backend\.env not found. Create it with DB_PASSWORD and ADMIN_PASSWORD set.
    exit /b 1
)

if not exist "vendor\autoload.php" (
    echo Installing PHP dependencies...
    call composer install
    if errorlevel 1 exit /b 1
)

if not exist "public\storage" (
    echo Creating storage link...
    php artisan storage:link
)

echo Running migrations...
php artisan migrate --force
if errorlevel 1 exit /b 1

echo Starting API on http://localhost:8000 and your LAN IP, port 8000
php artisan serve --host=0.0.0.0 --port=8000
exit /b 0

:frontend
title Smart QR Menu - Frontend
cd /d "%~dp0frontend"

if not exist "node_modules" (
    echo Installing npm dependencies...
    call npm install
    if errorlevel 1 exit /b 1
)

echo Starting React app on http://localhost:5173 and your LAN IP, port 5173
call npm run dev -- --host 0.0.0.0 --port 5173
exit /b 0
