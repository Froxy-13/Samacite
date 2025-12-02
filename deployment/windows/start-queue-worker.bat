@echo off
:: Script pour démarrer le queue worker sur Windows
:: Double-cliquez pour lancer

cd /d "%~dp0"
cd ..

echo ===========================================
echo   SamaCite Queue Worker
echo ===========================================
echo.
echo Demarrage du worker...
echo Appuyez sur Ctrl+C pour arreter
echo.

:loop
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
echo.
echo Worker arrete. Redemarrage dans 5 secondes...
timeout /t 5 /nobreak
goto loop
