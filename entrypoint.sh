#!/bin/sh
set -e

echo " Starting Pusat Data Indonesia Bali..."
echo "   Environment: ${APP_ENV}"
echo "   DB Host: ${DB_HOST}"
echo "   PORT: ${PORT}"

# Inject PORT ke nginx config
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/http.d/default.conf

# ── 1. Pastikan storage directories ada (penting kalau pakai Volume) ──
mkdir -p storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 777 storage bootstrap/cache

# ── 2. Clear config cache dulu (supaya env vars Railway terbaca) ──────
php artisan config:clear
php artisan cache:clear || true


# ── 3. Jalankan migration otomatis ────────────────────────────────────
# echo "  Running migrations..."
# if [ "$APP_ENV" != "production" ]; then
#   echo " Fresh migrate allowed..."
#   php artisan migrate:fresh --seed --force
# else
#   echo "Fresh migrate blocked in production"
#   php artisan migrate --force
# fi

echo " Running migrations..."
php artisan migrate --seed --force

# ── 4. Import wilayah Bali ───────────────────────────────────────────────────
# if [ "$IMPORT_WILAYAH" = "true" ]; then
# echo " Importing wilayah Bali..."
# php artisan import:wilayah-bali
# fi

# ── 5. Storage link ───────────────────────────────────────────────────
echo " Creating storage link..."
php artisan storage:link || true

# ── 6. Cache ulang untuk production performance ───────────────────────
echo " Caching config & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo " Setup selesai, starting server..."

# ── 7. Jalankan Supervisor (Nginx + PHP-FPM) ──────────────────────────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf