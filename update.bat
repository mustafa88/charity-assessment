@echo off
setlocal EnableDelayedExpansion
title Update - Family Assessment System
cd /d "%~dp0"

set "PHP_EXE=%~dp0..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=%~dp0php\php.exe"
if not exist "%PHP_EXE%" (
  echo [ERROR] Portable PHP not found. Place a "php" folder next to the project.
  pause & exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Git is not installed on this computer.
  echo Install Git from https://git-scm.com then try again.
  pause & exit /b 1
)

rem USB/removable drives don't record file ownership, which makes Git treat this
rem folder as untrusted ("dubious ownership") on some computers. Whitelist it
rem quietly so git pull below doesn't fail with that error.
git config --global --get-all safe.directory 2>nul | findstr /L /C:"%CD%" >nul
if errorlevel 1 (
  git config --global --add safe.directory "%CD%" >nul 2>&1
)

echo.
echo  =========================================
echo   Fetching the latest update from GitHub
echo  =========================================
echo.

rem Quick database backup before updating, just in case
if exist "database\database.sqlite" (
  if not exist "backups" mkdir "backups"
  for /f %%t in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"') do set "STAMP=%%t"
  copy /y "database\database.sqlite" "backups\database_before-update_!STAMP!.sqlite" >nul
  echo [Backup] backups\database_before-update_!STAMP!.sqlite
)

echo [1/3] Pulling latest changes ^(git pull^)...
git pull
if errorlevel 1 (
  echo.
  echo [WARNING] git pull failed. Check your internet connection and repository access.
  pause & exit /b 1
)

echo [2/3] Updating database tables...
"%PHP_EXE%" artisan migrate --force

echo [3/3] Clearing cache...
"%PHP_EXE%" artisan config:clear >nul 2>&1
"%PHP_EXE%" artisan view:clear   >nul 2>&1
"%PHP_EXE%" artisan route:clear  >nul 2>&1

echo.
echo  =========================================
echo   Update completed. Your data is safe.
echo  =========================================
echo.
pause
