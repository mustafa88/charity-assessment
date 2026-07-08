@echo off
title Backup - Family Assessment System
cd /d "%~dp0"

set "SRC=%~dp0database\database.sqlite"
set "DEST_DIR=%~dp0backups"

if not exist "%SRC%" (
  echo.
  echo [ERROR] Database file not found:
  echo %SRC%
  echo.
  pause
  exit /b 1
)

if not exist "%DEST_DIR%" mkdir "%DEST_DIR%"

rem Reliable timestamp (year-month-day_hour-minute-second) via PowerShell
for /f %%t in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"') do set "STAMP=%%t"

set "DEST=%DEST_DIR%\database_%STAMP%.sqlite"

copy /y "%SRC%" "%DEST%" >nul

if exist "%DEST%" (
  echo.
  echo  =========================================
  echo   Backup completed successfully
  echo  =========================================
  echo.
  echo   File: backups\database_%STAMP%.sqlite
  echo.
  rem Keep only the last 30 backups, delete older ones
  powershell -NoProfile -Command "Get-ChildItem -Path '%DEST_DIR%\database_*.sqlite' | Sort-Object LastWriteTime -Descending | Select-Object -Skip 30 | Remove-Item -Force"
) else (
  echo.
  echo [ERROR] Copy operation failed.
  echo.
)

pause
