@echo off
chcp 65001 >nul
title أول تثبيت - منظومة تقييم العائلات
cd /d "%~dp0"

set "PHP_EXE=%~dp0..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=%~dp0php\php.exe"
if not exist "%PHP_EXE%" (
  echo [خطأ] لم يُعثر على PHP المحمول. ضع مجلد php بجانب المشروع.
  pause & exit /b 1
)

echo.
echo  =========================================
echo   أول تثبيت للبرنامج (مرة واحدة فقط)
echo  =========================================
echo.

rem 1) ملف الإعدادات .env
if not exist ".env" (
  copy /y ".env.example" ".env" >nul
  echo [1/4] أُنشئ ملف .env
  "%PHP_EXE%" artisan key:generate --force
) else (
  echo [1/4] .env موجود مسبقاً - تخطٍّ
)

rem 2) ملف قاعدة البيانات الفارغ
if not exist "database\database.sqlite" (
  type nul > "database\database.sqlite"
  echo [2/4] أُنشئت قاعدة بيانات فارغة
) else (
  echo [2/4] قاعدة البيانات موجودة - تخطٍّ
)

rem 3) بناء الجداول
echo [3/4] بناء جداول قاعدة البيانات...
"%PHP_EXE%" artisan migrate --force

rem 4) تنظيف الكاش
"%PHP_EXE%" artisan config:clear >nul 2>&1
"%PHP_EXE%" artisan view:clear   >nul 2>&1
echo [4/4] تم التنظيف

echo.
echo  =========================================
echo   اكتمل التثبيت بنجاح.
echo  =========================================
echo.
echo   الخطوة التالية:
echo   1) شغّل «تشغيل-البرنامج.bat»
echo   2) سجّل حساب الدخول (أول مستخدم) من صفحة التسجيل
echo   3) افتح «سياسة النقاط» واحفظ لإنشاء السياسة الأولى v1
echo   4) ابدأ بإدخال العائلات
echo.
pause
