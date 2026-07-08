@echo off
title First Install - Family Assessment System
cd /d "%~dp0"

set "PHP_EXE=%~dp0..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=%~dp0php\php.exe"
if not exist "%PHP_EXE%" (
  echo [ERROR] Portable PHP not found. Place a "php" folder next to the project.
  pause & exit /b 1
)

echo.
echo  =========================================
echo   First-time setup ^(run this once only^)
echo  =========================================
echo.

rem 1) .env config file
if not exist ".env" (
  copy /y ".env.example" ".env" >nul
  echo [1/4] Created .env file
  "%PHP_EXE%" artisan key:generate --force
) else (
  echo [1/4] .env already exists - skipping
)

rem 2) Empty database file
if not exist "database\database.sqlite" (
  type nul > "database\database.sqlite"
  echo [2/4] Created empty database
) else (
  echo [2/4] Database already exists - skipping
)

rem 3) Build tables
echo [3/4] Building database tables...
"%PHP_EXE%" artisan migrate --force

rem 4) Clear cache
"%PHP_EXE%" artisan config:clear >nul 2>&1
"%PHP_EXE%" artisan view:clear   >nul 2>&1
echo [4/4] Cache cleared

echo.
echo  =========================================
echo   Setup completed successfully.
echo  =========================================
echo.
echo   Next steps:
echo   1^) Run "run.bat"
echo   2^) Register a login account ^(first user^) from the sign-up page
echo   3^) Open "Scoring Policy" and save to create the first policy ^(v1^)
echo   4^) Start entering families
echo.
pause
