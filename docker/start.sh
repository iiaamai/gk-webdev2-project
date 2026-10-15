#!/usr/bin/env sh
set -e

cd /var/www/html

PORT="${PORT:-10000}"

sed -i "s/listen 8080;/listen ${PORT};/" /etc/nginx/conf.d/default.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache

php-fpm -D
exec nginx -g 'daemon off;'
