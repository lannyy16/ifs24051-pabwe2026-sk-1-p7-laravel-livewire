@echo off
REM Jalankan test + coverage 100% (butuh Xdebug/PCOV) - Windows
php artisan config:clear
php artisan test --coverage --min=100
