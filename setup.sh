#!/bin/bash

echo "==================================="
echo "WebAI Auditor PHP - Setup Script"
echo "==================================="

# Check PHP version
echo "Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP version: $PHP_VERSION"

if ! php -r "version_compare('$PHP_VERSION', '8.2', '>=');" 2>/dev/null; then
    echo "Error: PHP 8.2 or higher is required."
    exit 1
fi

# Check Composer
echo "Checking Composer..."
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed."
    exit 1
fi

# Navigate to backend directory
cd backend || exit 1

# Install dependencies
echo "Installing PHP dependencies..."
composer install --no-interaction

# Copy environment file
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Create symbolic links
echo "Creating symbolic links..."
php artisan storage:link

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear and cache configs
echo "Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache

echo ""
echo "==================================="
echo "Setup completed successfully!"
echo "==================================="
echo ""
echo "To start the development server:"
echo "  cd backend && php artisan serve"
echo ""
echo "The API will be available at: http://localhost:8000"
echo ""
echo "Open frontend/index.html in your browser"
echo ""
