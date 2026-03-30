# ─────────────────────────────────────────────────────────────────
# Helper script Docker untuk Windows PowerShell
# Cara pakai: .\docker.ps1 [perintah]
# ─────────────────────────────────────────────────────────────────

param([string]$Command = "help")

$APP_CONTAINER = "pusat_data_app"

switch ($Command) {

    # ── Build & Start ─────────────────────────────────────────────
    "build" {
        Write-Host "🔨 Building Docker images..." -ForegroundColor Cyan
        docker compose build --no-cache
    }

    "start" {
        Write-Host "🚀 Starting containers..." -ForegroundColor Green
        docker compose up -d
        Write-Host "✅ App berjalan di http://localhost:8000" -ForegroundColor Green
    }

    "stop" {
        Write-Host "🛑 Stopping containers..." -ForegroundColor Yellow
        docker compose down
    }

    "restart" {
        Write-Host "🔄 Restarting containers..." -ForegroundColor Cyan
        docker compose down
        docker compose up -d
    }

    # ── Laravel Commands ──────────────────────────────────────────
    "migrate" {
        Write-Host "🗄️  Running migrations..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan migrate
    }

    "migrate-fresh" {
        Write-Host "🗄️  Fresh migration dengan seeder..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan migrate:fresh --seed
    }

    "seed" {
        Write-Host "🌱 Running seeders..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan db:seed
    }

    "key" {
        Write-Host "🔑 Generating APP_KEY..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan key:generate
    }

    "cache-clear" {
        Write-Host "🧹 Clearing all cache..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan optimize:clear
    }

    # ── Shell Access ──────────────────────────────────────────────
    "shell" {
        Write-Host "🐚 Masuk ke shell container..." -ForegroundColor Cyan
        docker exec -it $APP_CONTAINER sh
    }

    # ── Logs ──────────────────────────────────────────────────────
    "logs" {
        docker compose logs -f app
    }

    # ── Full Setup (pertama kali) ─────────────────────────────────
    "setup" {
        Write-Host "⚙️  Setup pertama kali..." -ForegroundColor Cyan
        docker compose build
        docker compose up -d
        Write-Host "⏳ Menunggu database siap..." -ForegroundColor Yellow
        Start-Sleep -Seconds 10
        Write-Host "🔑 Generating APP_KEY..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan key:generate
        Write-Host "🗄️  Running migrations..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan migrate
        Write-Host "🔗 Creating storage link..." -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan storage:link
        Write-Host ""
        Write-Host "✅ Setup selesai! App berjalan di http://localhost:8000" -ForegroundColor Green
    }

    # ── Generate APP_KEY untuk Railway ────────────────────────────
    "key-show" {
        Write-Host "🔑 APP_KEY untuk Railway:" -ForegroundColor Cyan
        docker exec $APP_CONTAINER php artisan key:generate --show
    }

    default {
        Write-Host ""
        Write-Host "Penggunaan: .\docker.ps1 [perintah]" -ForegroundColor White
        Write-Host ""
        Write-Host "Perintah tersedia:" -ForegroundColor Yellow
        Write-Host "  build         - Build ulang Docker image"
        Write-Host "  start         - Jalankan semua container"
        Write-Host "  stop          - Hentikan semua container"
        Write-Host "  restart       - Restart semua container"
        Write-Host "  migrate       - php artisan migrate"
        Write-Host "  migrate-fresh - php artisan migrate:fresh --seed"
        Write-Host "  seed          - php artisan db:seed"
        Write-Host "  key           - Generate APP_KEY"
        Write-Host "  key-show      - Tampilkan APP_KEY (untuk Railway)"
        Write-Host "  cache-clear   - Bersihkan semua cache"
        Write-Host "  shell         - Masuk ke shell container app"
        Write-Host "  logs          - Lihat log container app"
        Write-Host "  setup         - Setup lengkap (pertama kali)"
        Write-Host ""
    }
}