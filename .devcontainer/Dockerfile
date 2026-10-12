FROM php:8.5-cli

# System packages and the PHP extensions Laravel and this app need.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip default-mysql-client \
        libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip bcmath gd pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && printf 'upload_max_filesize=20M\npost_max_size=25M\nmemory_limit=512M\n' > "$PHP_INI_DIR/conf.d/zz-dev.ini"
