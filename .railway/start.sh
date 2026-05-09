#!/bin/bash

# Set up environment
export APP_ENV=production
export APP_DEBUG=false

# Install dependencies
composer install --no-dev --optimize-autoloader

# Create storage links
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache

# Start the application
php artisan serve --host=0.0.0.0 --port=$PORT
