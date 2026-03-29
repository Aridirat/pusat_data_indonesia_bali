FROM php:8.2-cli

WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libxml2-dev libonig-dev libzip-dev \
    libfreetype6-dev libjpeg-dev libwebp-dev libicu-dev

# Configure GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Buat folder Laravel
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Permission
RUN chmod -R 777 storage bootstrap/cache

# Clear & cache config
RUN php artisan config:clear || true
RUN php artisan route:clear || true

# Vite build
RUN rm -rf node_modules package-lock.json
RUN npm install
RUN npm run build

# Expose port
EXPOSE 8000

# Run Laravel
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}