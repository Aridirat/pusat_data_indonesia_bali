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

# Install imagick
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Hapus build tools setelah selesai
RUN apk del autoconf g++ make

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

COPY package.json package-lock.json* ./

RUN npm ci --ignore-scripts

COPY . .

ARG DISABLE_WAYFINDER=true
ENV DISABLE_WAYFINDER=${DISABLE_WAYFINDER}
RUN npm run build

RUN rm -rf node_modules

RUN composer dump-autoload --optimize

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN mkdir -p /var/log/supervisor /var/run

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

# Copy entrypoint — 2 RUN terpisah, tidak pakai \n literal
COPY entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r//' /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]