@echo off
cd /d %~dp0
echo ---------------------------------------------------
echo Dialysis Transfer Manager - Launcher
echo ---------------------------------------------------
echo.
echo Preparing server...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "launch_local_preview.ps1"

echo.
echo Server stopped.
pause
