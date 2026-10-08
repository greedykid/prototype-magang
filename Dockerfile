# syntax=docker/dockerfile:1

# ── Tahap 1: dependency PHP ──────────────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --ignore-platform-req=ext-gd

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction --ignore-platform-req=ext-gd


# ── Tahap 2: aset frontend ───────────────────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN mkdir -p storage/framework/views && npm run build


# ── Tahap 3: aplikasi (php-fpm) ──────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache --virtual .build-deps \
        oniguruma-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mbstring opcache zip gd \
    && apk del .build-deps \
    && apk add --no-cache oniguruma libzip libpng libjpeg-turbo freetype

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-simasadi.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-simasadi.conf

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN rm -f bootstrap/cache/*.php \
    && mkdir -p /var/www/data storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]


# ── Tahap 4: web (nginx) ─────────────────────────────────────────────────────
FROM nginx:alpine AS web

COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
