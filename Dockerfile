FROM php:8.4-fpm-alpine

# Install dependencies sistem
RUN apk add --no-cache \
    linux-headers \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    icu-dev \
    mariadb-client \
    oniguruma-dev

# Install ekstensi PHP yang dibutuhkan Laravel & Filament
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl intl bcmath

# Copy Composer dari official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Atur ownership ke user www-data (standar FPM)
RUN chown -R www-data:www-data /var/www/html