# syntax=docker/dockerfile:1

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM composer:2 AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize

FROM php:8.4-fpm-bookworm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        libpq-dev \
        libzip-dev \
        libpng-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        zip \
        gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /var/www/html /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]
