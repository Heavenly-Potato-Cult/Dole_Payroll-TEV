#!/bin/bash

export APP_ENV=production
export APP_DEBUG=false

php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan serve --host=0.0.0.0 --port=$PORT