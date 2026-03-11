@echo off
cd /d "%~dp0"
echo Demarrage du projet MEUBLE DRISS...
echo.
start "Laravel - php artisan serve" cmd /k "php artisan serve"
start "Vite - npm run dev" cmd /k "npm run dev"
echo.
echo Deux fenetres ouvertes : Laravel (port 8000) et Vite (build front).
echo Fermez les fenetres pour arreter les serveurs.
timeout /t 3
