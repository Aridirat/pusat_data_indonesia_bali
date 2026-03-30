#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Helper script untuk development dengan Docker
# Cara pakai: ./docker.sh [perintah]
# ─────────────────────────────────────────────────────────────

set -e

APP_CONTAINER="pusat_data_app"

case "$1" in

  # ── Build & Start ──────────────────────────────────────────
  build)
    echo "🔨 Building Docker images..."
    docker compose build --no-cache
    ;;

  start)
    echo "🚀 Starting containers..."
    docker compose up -d
    echo "✅ App berjalan di http://localhost:8000"
    ;;

  stop)
    echo "🛑 Stopping containers..."
    docker compose down
    ;;

  restart)
    echo "🔄 Restarting containers..."
    docker compose down && docker compose up -d
    ;;

  # ── Laravel Commands ───────────────────────────────────────
  migrate)
    echo "🗄️  Running migrations..."
    docker exec $APP_CONTAINER php artisan migrate
    ;;

  migrate-fresh)
    echo "🗄️  Fresh migration dengan seeder..."
    docker exec $APP_CONTAINER php artisan migrate:fresh --seed
    ;;

  seed)
    echo "🌱 Running seeders..."
    docker exec $APP_CONTAINER php artisan db:seed
    ;;

  key)
    echo "🔑 Generating APP_KEY..."
    docker exec $APP_CONTAINER php artisan key:generate
    ;;

  cache-clear)
    echo "🧹 Clearing all cache..."
    docker exec $APP_CONTAINER php artisan optimize:clear
    ;;

  # ── Shell Access ───────────────────────────────────────────
  shell)
    echo "🐚 Masuk ke shell container..."
    docker exec -it $APP_CONTAINER sh
    ;;

  # ── Logs ───────────────────────────────────────────────────
  logs)
    docker compose logs -f app
    ;;

  # ── Full Setup (pertama kali) ──────────────────────────────
  setup)
    echo "⚙️  Setup pertama kali..."
    docker compose build
    docker compose up -d
    sleep 5
    echo "📦 Generating APP_KEY..."
    docker exec $APP_CONTAINER php artisan key:generate
    echo "🗄️  Running migrations..."
    docker exec $APP_CONTAINER php artisan migrate
    echo "🔗 Creating storage link..."
    docker exec $APP_CONTAINER php artisan storage:link
    echo ""
    echo "✅ Setup selesai! App berjalan di http://localhost:8000"
    ;;

  *)
    echo "Penggunaan: ./docker.sh [perintah]"
    echo ""
    echo "Perintah tersedia:"
    echo "  build         - Build ulang Docker image"
    echo "  start         - Jalankan semua container"
    echo "  stop          - Hentikan semua container"
    echo "  restart       - Restart semua container"
    echo "  migrate       - php artisan migrate"
    echo "  migrate-fresh - php artisan migrate:fresh --seed"
    echo "  seed          - php artisan db:seed"
    echo "  key           - Generate APP_KEY"
    echo "  cache-clear   - Bersihkan semua cache"
    echo "  shell         - Masuk ke shell container app"
    echo "  logs          - Lihat log container app"
    echo "  setup         - Setup lengkap (pertama kali)"
    ;;
esac