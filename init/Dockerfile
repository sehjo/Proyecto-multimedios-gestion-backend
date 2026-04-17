FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts

FROM php:8.4-cli-alpine

RUN apk add --no-cache icu-dev oniguruma-dev libxml2-dev curl \
    && docker-php-ext-install pdo pdo_mysql intl mbstring xml \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache

RUN cp .env.example .env \
    && sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env \
    && sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env \
    && sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env \
    && sed -i '/^DB_/d' .env \
    && php artisan key:generate --force

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
