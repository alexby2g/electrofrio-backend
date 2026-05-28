FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chmod -R 775 storage bootstrap/cache \
    && sed -i 's/\r$//' render-start.sh \
    && chmod +x render-start.sh

EXPOSE 10000

CMD ["sh", "render-start.sh"]
