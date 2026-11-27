@echo off
setlocal
cd /d "%~dp0"
title Smart QR Menu - Frontend

if not exist "node_modules" (
    echo Installing npm dependencies...
    call npm install
    if errorlevel 1 ( pause & exit /b 1 )
)

echo Starting React app on http://localhost:5173
call npm run dev -- --host 127.0.0.1 --port 5173
pause
