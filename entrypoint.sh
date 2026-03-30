#!/bin/sh
set -e

echo "🚀 Starting Pusat Data Indonesia Bali..."
echo "   Environment: ${APP_ENV}"
echo "   DB Host: ${DB_HOST}"

# ── 1. Pastikan storage directories ada (penting kalau pakai Volume) ──
mkdir -p storage/app/public \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

# ── 2. Clear config cache dulu (supaya env vars Railway terbaca) ──────
php artisan config:clear
php artisan cache:clear

# ── 3. Jalankan migration otomatis ────────────────────────────────────
echo "🗄️  Running migrations..."
php artisan migrate --force

# ── 4. Storage link ───────────────────────────────────────────────────
echo "🔗 Creating storage link..."
php artisan storage:link || true

# ── 5. Cache ulang untuk production performance ───────────────────────
echo "⚡ Caching config & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Setup selesai, starting server..."

# ── 6. Jalankan Supervisor (Nginx + PHP-FPM) ──────────────────────────
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf