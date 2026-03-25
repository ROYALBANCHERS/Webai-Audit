#!/bin/bash

# Setup script for Render deployment
# This script creates the SQLite database and runs migrations

echo "Starting WebAI Auditor setup..."

# Create storage directories if they don't exist
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/app/screenshots
mkdir -p storage/app/exports

# Set permissions
chmod -R 775 storage

# Create SQLite database if it doesn't exist
if [ ! -f storage/database.sqlite ]; then
    echo "Creating SQLite database..."
    touch storage/database.sqlite
fi

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache routes
php artisan route:cache

echo "Setup complete!"
