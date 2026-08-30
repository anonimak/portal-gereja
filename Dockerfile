# syntax=docker/dockerfile:1
# ============================================================
# Dockerfile PRODUCTION (T7) — multi-stage, self-contained
#   Stage 1 builder : composer install (no-dev) + npm build
#   Stage 2 app     : PHP-FPM runtime (artefak dari builder)
#   Stage 3 nginx   : hanya public/ + konfigurasi nginx
# Prasyarat build  : .env.example (bukan .env) — secret TIDAK di-bake ke image
# ============================================================

###############################################################################
# STAGE 1 — builder: dependensi PHP + aset frontend
###############################################################################
FROM php:8.4-fpm-alpine AS builder

# Toolchain build + library sistem (nodejs/npm untuk build frontend)
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    linux-headers \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    git \
    curl \
    unzip \
    zip \
    nodejs \
    npm

# Ekstensi PHP yang dibutuhkan Laravel & Filament (dikompilasi di builder)
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl intl bcmath opcache

# Composer: pin major 2 (hindari :latest yang non-reproducible)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy manifest dulu agar layer cache optimal
COPY composer.json composer.lock package.json package-lock.json ./

# APP_KEY dummy HANYA untuk keperluan artisan saat build (tidak masuk image runtime)
ENV APP_KEY="base64:dU1YbU9QRk1IOWF6Z0hVc1B4S0ZkQ3JYbHBNcXpRdG9ZR2lNeUZ3UzNlUT0="

# Install dependensi (lock file -> reproducible; --no-dev untuk produksi)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && npm ci --no-audit --no-fund

# Copy seluruh source (vendor/node_modules/.env dikecualikan via .dockerignore)
COPY . .

# Autoload final + script framework (package:discover, filament:upgrade) + build aset
RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump \
    && npm run build \
    && rm -rf node_modules

###############################################################################
# STAGE 2 — runtime PHP-FPM (image aplikasi)
###############################################################################
FROM php:8.4-fpm-alpine AS app

# Library runtime (tanpa toolchain build -> image kecil & permukaan serangan kecil)
RUN apk add --no-cache \
    libzip \
    icu-libs \
    oniguruma \
    mariadb-client \
    curl

# Salin ekstensi PHP yang sudah dikompilasi dari builder (tidak perlu compile ulang)
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Tuning OPcache untuk produksi
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=0'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=16'; \
      echo 'opcache.max_accelerated_files=10000'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.save_comments=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Entrypoint: permission storage, APP_KEY, migrate, storage:link, optimize
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

###############################################################################
# STAGE 3 — nginx (hanya public/ + konfigurasi, tanpa source code)
###############################################################################
FROM nginx:1.27-alpine AS nginx

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=builder --chown=nginx:nginx /var/www/html/public /var/www/html/public

EXPOSE 80
