#!/bin/bash

export APP_ENV=production
export APP_DEBUG=false

composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan serve --host=0.0.0.0 --port=$PORT