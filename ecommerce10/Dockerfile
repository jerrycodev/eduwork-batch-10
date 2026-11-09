# PHP dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

# Frontend production assets
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN npm run build

# Shared Laravel runtime filesystem
FROM php:8.4-fpm-alpine AS app-base

RUN apk add --no-cache \
        fcgi \
        libjpeg-turbo \
        libpng \
        libwebp \
        freetype \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        opcache \
        pcntl \
        pdo_mysql \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

RUN rm -f public/storage \
    && ln -s ../storage/app/public public/storage \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

RUN rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php \
    && php artisan package:discover --ansi

COPY docker/entrypoint.sh /usr/local/bin/laravel-entrypoint
RUN chmod +x /usr/local/bin/laravel-entrypoint

ENTRYPOINT ["laravel-entrypoint"]

# PHP-FPM application service
FROM app-base AS app
EXPOSE 9000
CMD ["php-fpm", "-F"]

# Nginx serves the exact public assets built into the same release image
FROM nginx:1.27-alpine AS web
WORKDIR /var/www/html
COPY --from=app-base /var/www/html/public ./public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
