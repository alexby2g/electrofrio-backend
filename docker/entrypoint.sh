#!/usr/bin/env sh
set -e

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
