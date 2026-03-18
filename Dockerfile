# Stage 1: Build frontend assets
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY webpack.config.js tsconfig.json ./
COPY assets/ assets/
RUN npm run build

# Stage 2: PHP production image
FROM php:8.4-apache AS production

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libicu-dev \
    unzip \
    && docker-php-ext-install pdo_pgsql intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure opcache for production
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-prod.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy Apache config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Set APP_ENV at build time so Symfony skips .env file loading
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Install PHP dependencies (no dev)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application source
COPY . .

# Copy built frontend assets from node stage
COPY --from=node-builder /app/public/build/ public/build/

# Skip post-install-cmd during build — cache warmup runs in start.sh
# where Railway's runtime env vars are available
RUN composer dump-autoload --optimize

# Create and set permissions for var/ directory (cache, logs)
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var/

# Copy and prepare startup script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]
