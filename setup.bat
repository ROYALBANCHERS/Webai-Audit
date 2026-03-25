@echo off
echo ===================================
echo WebAI Auditor PHP - Setup Script
echo ===================================
echo.

REM Check PHP version
echo Checking PHP version...
php -v >nul 2>&1
if errorlevel 1 (
    echo Error: PHP is not installed or not in PATH.
    echo Please install PHP 8.2 or higher from https://windows.php.net/download/
    pause
    exit /b 1
)

for /f "tokens=2" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo PHP version: %PHP_VERSION%

REM Check Composer
echo Checking Composer...
composer -V >nul 2>&1
if errorlevel 1 (
    echo Error: Composer is not installed or not in PATH.
    echo Please install Composer from https://getcomposer.org/download/
    pause
    exit /b 1
)

REM Navigate to backend directory
cd backend

REM Install dependencies
echo Installing PHP dependencies...
composer install --no-interaction

REM Copy environment file
if not exist .env (
    echo Creating .env file...
    copy .env.example .env
    php artisan key:generate
)

REM Create symbolic links (Windows requires admin rights)
echo Creating storage links...
php artisan storage:link

REM Run migrations
echo Running database migrations...
php artisan migrate --force

REM Clear and cache configs
echo Optimizing application...
php artisan config:clear
php artisan cache:clear
php artisan config:cache

echo.
echo ===================================
echo Setup completed successfully!
echo ===================================
echo.
echo To start the development server:
echo   cd backend
echo   php artisan serve
echo.
echo The API will be available at: http://localhost:8000
echo.
echo Open frontend\index.html in your browser
echo.
pause
