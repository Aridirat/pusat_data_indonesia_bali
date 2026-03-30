FROM php:8.2-fpm-alpine

# Install system dependencies (Alpine pakai apk, BUKAN apt-get)
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    ghostscript \
    imagemagick-dev \
    imagemagick \
    ttf-freefont \
    nginx \
    supervisor \
    nodejs \
    npm \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    xml \
    soap

# Install imagick — pakai apk (Alpine), bukan apt-get (Debian/Ubuntu)
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Hapus build tools setelah selesai (image lebih kecil)
RUN apk del autoconf g++ make

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copy package.json dulu (layer caching)
COPY package.json package-lock.json* ./

# Install Node dependencies
RUN npm ci --ignore-scripts

# Copy seluruh project
COPY . .

# Buat direktori yang dibutuhkan & set permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Build assets Vite — nonaktifkan wayfinder saat build Docker
ENV DISABLE_WAYFINDER=true
RUN npm run build

# Hapus node_modules setelah build (tidak dibutuhkan di production)
RUN rm -rf node_modules

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Copy konfigurasi Nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy konfigurasi PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy konfigurasi Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Buat direktori yang dibutuhkan & set permissions
RUN mkdir -p /var/log/supervisor /var/run

# Expose port
EXPOSE 80

# Jalankan Supervisor (mengelola Nginx + PHP-FPM sekaligus)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]