@echo off
chcp 65001 >nul
title تحديث البرنامج - منظومة تقييم العائلات
cd /d "%~dp0"

set "PHP_EXE=%~dp0..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=%~dp0php\php.exe"
if not exist "%PHP_EXE%" (
  echo [خطأ] لم يُعثر على PHP المحمول. ضع مجلد php بجانب المشروع.
  pause & exit /b 1
)

where git >nul 2>&1
if errorlevel 1 (
  echo [خطأ] Git غير مثبّت على هذا الجهاز.
  echo ثبّت Git من https://git-scm.com ثم أعد المحاولة.
  pause & exit /b 1
)

echo.
echo  =========================================
echo   جلب آخر تحديث للبرنامج من GitHub
echo  =========================================
echo.

rem نسخة احتياطية سريعة لقاعدة البيانات قبل التحديث (احتياطاً)
if exist "database\database.sqlite" (
  if not exist "backups" mkdir "backups"
  for /f %%t in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"') do set "STAMP=%%t"
  copy /y "database\database.sqlite" "backups\database_before-update_%STAMP%.sqlite" >nul
  echo [نسخة احتياطية] backups\database_before-update_%STAMP%.sqlite
)

echo [1/3] جلب الأحدث (git pull)...
git pull
if errorlevel 1 (
  echo.
  echo [تنبيه] فشل git pull. تأكّد من الاتصال بالإنترنت وصلاحية الوصول للمستودع.
  pause & exit /b 1
)

echo [2/3] تحديث جداول قاعدة البيانات...
"%PHP_EXE%" artisan migrate --force

echo [3/3] تنظيف الكاش...
"%PHP_EXE%" artisan config:clear >nul 2>&1
"%PHP_EXE%" artisan view:clear   >nul 2>&1
"%PHP_EXE%" artisan route:clear  >nul 2>&1

echo.
echo  =========================================
echo   تم التحديث بنجاح. بياناتك محفوظة كما هي.
echo  =========================================
echo.
pause
