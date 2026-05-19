# Stage 1: builder
FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache \
    git curl zip unzip libpng-dev libjpeg-turbo-dev libwebp-dev \
    libzip-dev icu-dev postgresql-dev oniguruma-dev \
    libxml2-dev freetype-dev linux-headers

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql \
    zip gd intl bcmath \
    opcache pcntl sockets exif \
    mbstring xml

RUN apk add --no-cache $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN if [ -f composer.lock ]; then composer install --no-dev --no-scripts --no-autoloader --prefer-dist; fi

COPY . .
RUN if [ -f composer.json ]; then composer dump-autoload --optimize --no-dev || true; fi

# Stage 2: production image
FROM php:8.4-fpm-alpine AS production

RUN apk add --no-cache \
    bash curl libpng libjpeg-turbo libwebp libzip icu-libs postgresql-libs oniguruma libxml2 freetype

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

WORKDIR /var/www/html
COPY --from=builder /var/www/html .
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www
RUN mkdir -p storage bootstrap/cache \
 && chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache

USER www

EXPOSE 9000
CMD ["php-fpm"]

# Development stage (includes dev tools)
FROM production AS development
USER root
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache bash nodejs npm
USER www