@echo off
rem Starts backend (http://localhost:8000) and frontend (http://localhost:5173) in separate windows.
cd /d "%~dp0"
start "Smart QR Menu - Backend" cmd /k "%~dp0backend\run.bat"
start "Smart QR Menu - Frontend" cmd /k "%~dp0frontend\run.bat"
