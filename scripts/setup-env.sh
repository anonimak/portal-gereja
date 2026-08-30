#!/bin/sh
# ============================================================
# Siapkan .env dari .env.example + generate secret kuat
# TANPA perlu PHP di host (APP_KEY = base64 32 byte acak).
# Aman: .env sudah di-gitignore, jangan pernah di-commit.
# ============================================================
set -e
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  cp .env.example .env
  echo "[env] Membuat .env dari .env.example"
fi

# APP_KEY — wajib, tanpa ini Laravel tidak boot
if grep -qE '^APP_KEY=$|^APP_KEY=""$' .env; then
  KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
  sed -i "s|^APP_KEY=.*|APP_KEY=${KEY}|" .env
  echo "[env] APP_KEY digenerate (baru)."
fi

# DB_PASSWORD
if grep -qE '^DB_PASSWORD=$|^DB_PASSWORD=""$' .env; then
  P="$(openssl rand -base64 24 | tr -d '\n')"
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${P}|" .env
  echo "[env] DB_PASSWORD digenerate (baru)."
fi

# MARIADB_ROOT_PASSWORD
if grep -qE '^MARIADB_ROOT_PASSWORD=$|^MARIADB_ROOT_PASSWORD=""$' .env; then
  R="$(openssl rand -base64 24 | tr -d '\n')"
  sed -i "s|^MARIADB_ROOT_PASSWORD=.*|MARIADB_ROOT_PASSWORD=${R}|" .env
  echo "[env] MARIADB_ROOT_PASSWORD digenerate (baru)."
fi

echo "[env] Selesai. .env siap — JANGAN commit file ini."
