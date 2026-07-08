@echo off
title Family Assessment System
cd /d "%~dp0"

rem ===== Location of the portable PHP on the USB drive =====
rem Looks first for a "php" folder next to the project, then inside it
set "PHP_EXE=%~dp0..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=%~dp0php\php.exe"

if not exist "%PHP_EXE%" (
  echo.
  echo [ERROR] Portable PHP not found.
  echo Place a "php" folder next to the project folder on the USB drive.
  echo.
  pause
  exit /b 1
)

echo.
echo  =========================================
echo   Family Assessment System
echo  =========================================
echo.
echo   Starting the local server...
echo   Do not close this window while using the program.
echo.

rem Clear any cached config that may hold old paths
"%PHP_EXE%" artisan config:clear >nul 2>&1
"%PHP_EXE%" artisan view:clear   >nul 2>&1

rem Open the browser after 2 seconds at the app address
start "" /b cmd /c "timeout /t 2 >nul & start http://127.0.0.1:8000"

rem Run the Laravel server (keeps running until you close this window)
"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000

echo.
echo   Server stopped. You can close this window.
pause
