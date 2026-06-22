@echo off
chcp 65001 >nul
title نسخة احتياطية - منظومة تقييم العائلات
cd /d "%~dp0"

set "SRC=%~dp0database\database.sqlite"
set "DEST_DIR=%~dp0backups"

if not exist "%SRC%" (
  echo.
  echo [خطأ] ملف قاعدة البيانات غير موجود:
  echo %SRC%
  echo.
  pause
  exit /b 1
)

if not exist "%DEST_DIR%" mkdir "%DEST_DIR%"

rem طابع زمني موثوق (سنة-شهر-يوم_ساعة-دقيقة-ثانية) عبر PowerShell
for /f %%t in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"') do set "STAMP=%%t"

set "DEST=%DEST_DIR%\database_%STAMP%.sqlite"

copy /y "%SRC%" "%DEST%" >nul

if exist "%DEST%" (
  echo.
  echo  =========================================
  echo   تمت النسخة الاحتياطية بنجاح
  echo  =========================================
  echo.
  echo   الملف: backups\database_%STAMP%.sqlite
  echo.
  rem الإبقاء على آخر 30 نسخة فقط وحذف الأقدم
  powershell -NoProfile -Command "Get-ChildItem -Path '%DEST_DIR%\database_*.sqlite' | Sort-Object LastWriteTime -Descending | Select-Object -Skip 30 | Remove-Item -Force"
) else (
  echo.
  echo [خطأ] فشلت عملية النسخ.
  echo.
)

pause
