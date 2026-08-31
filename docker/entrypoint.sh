#!/bin/sh
# ============================================================
# Entrypoint container (app / queue / scheduler)
# 1. permission storage & bootstrap/cache
# 2. pastikan APP_KEY ada (WAJIB) — generate sementara jika kosong
# 3. tunggu database siap
# 4. migrate --force (hanya service app; RUN_MIGRATIONS=false utk worker)
# 5. storage:link (idempotent)
# 6. optimize produksi (hanya service app)
# ============================================================
set -e

echo "[entrypoint] Menyiapkan permission storage & bootstrap/cache..."
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

if [ -z "${APP_KEY:-}" ]; then
  echo "[entrypoint] APP_KEY kosong — generate sementara."
  echo "[entrypoint] PRODUCTION: set APP_KEY di .env (php artisan key:generate)."
  APP_KEY="$(php artisan key:generate --show --force 2>/dev/null || php -r 'echo "base64:".base64_encode(random_bytes(32));')"
  export APP_KEY
fi

echo "[entrypoint] Menunggu database ${DB_HOST:-db}:${DB_PORT:-3306} siap..."
php -r '
  $host = getenv("DB_HOST") ?: "db";
  $port = (int) (getenv("DB_PORT") ?: 3306);
  for ($i = 0; $i < 60; $i++) {
    $c = @fsockopen($host, $port, $errno, $errstr, 2);
    if ($c) { fclose($c); exit(0); }
    sleep(2);
  }
  fwrite(STDERR, "[entrypoint] Database tidak dapat dijangkau setelah 120 detik.\n");
  exit(1);
'

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "[entrypoint] Menjalankan migrate --force..."
  php artisan migrate --force
else
  # Worker (queue/scheduler) TIDAK menjalankan migrasi, tapi harus menunggu
  # sampai service app selesai migrate (tabel `migrations` ada), supaya tidak
  # crash "table not found" saat start lebih dulu.
  echo "[entrypoint] Menunggu migrasi service app selesai (tabel migrations)..."
  php -r '
    $host = getenv("DB_HOST") ?: "db";
    $port = (int) (getenv("DB_PORT") ?: 3306);
    $db   = getenv("DB_DATABASE") ?: "portal_gereja";
    $user = getenv("DB_USERNAME") ?: "church_user";
    $pass = getenv("DB_PASSWORD") ?: "";
    $dsn  = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    for ($i = 0; $i < 90; $i++) {
      try {
        $pdo = new PDO($dsn, $user, $pass);
        $t   = $pdo->query("SHOW TABLES LIKE \"migrations\"")->fetch();
        if ($t) { echo "[entrypoint] Migrasi selesai, lanjut.\n"; exit(0); }
      } catch (Throwable $e) { /* db belum siap */ }
      sleep(2);
    }
    fwrite(STDERR, "[entrypoint] Migrasi tidak selesai setelah 180 detik.\n");
    exit(1);
  '
fi

echo "[entrypoint] Membuat symlink storage (public/storage)..."
php artisan storage:link || true

if [ "${APP_ENV:-production}" = "production" ] && [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "[entrypoint] Optimasi produksi (config/route/view cache)..."
  php artisan optimize || true
fi

echo "[entrypoint] Siap. Menjalankan: $*"
exec "$@"
