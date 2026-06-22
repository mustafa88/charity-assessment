@echo off
chcp 65001 >nul
title منظومة تقييم العائلات - مبرة عطاء
cd /d "%~dp0"

rem ===== مكان PHP المحمول على الفلاشة =====
rem يبحث أولاً عن مجلد php بجانب المشروع، ثم داخل المشروع
set "PHP_EXE=%~dp0..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=%~dp0php\php.exe"

if not exist "%PHP_EXE%" (
  echo.
  echo [خطأ] لم يتم العثور على PHP المحمول.
  echo ضع مجلد php بجانب مجلد المشروع على الفلاشة.
  echo.
  pause
  exit /b 1
)

echo.
echo  =========================================
echo   منظومة تقييم العائلات - مبرة عطاء
echo  =========================================
echo.
echo   جارٍ تشغيل الخادم المحلي...
echo   لا تغلق هذه النافذة أثناء استعمال البرنامج.
echo.

rem تنظيف أي إعدادات مخزّنة قد تحمل مسارات قديمة
"%PHP_EXE%" artisan config:clear >nul 2>&1
"%PHP_EXE%" artisan view:clear   >nul 2>&1

rem فتح المتصفح بعد ثانيتين على عنوان البرنامج
start "" /b cmd /c "timeout /t 2 >nul & start http://127.0.0.1:8000"

rem تشغيل خادم Laravel (يبقى يعمل حتى تغلق النافذة)
"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000

echo.
echo   تم إيقاف الخادم. يمكنك إغلاق النافذة.
pause
