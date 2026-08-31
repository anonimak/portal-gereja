#!/bin/sh
# ============================================================
# Pilih engine container secara otomatis: podman (prefer) atau docker.
# Host tetap bersih — TIDAK perlu install PHP/Composer/Node di host.
#   make up / down / logs / ... memanggil script ini.
# ============================================================
if command -v podman >/dev/null 2>&1; then
  if podman compose version >/dev/null 2>&1; then
    exec podman compose "$@"
  fi
  if command -v podman-compose >/dev/null 2>&1; then
    exec podman-compose "$@"
  fi
  echo "ERROR: podman terpasang tapi plugin compose tidak ditemukan." >&2
  echo "       Install podman-compose atau gunakan docker." >&2
  exit 1
fi

if command -v docker >/dev/null 2>&1; then
  if docker compose version >/dev/null 2>&1; then
    exec docker compose "$@"
  fi
  echo "ERROR: docker terpasang tapi plugin 'docker compose' tidak ditemukan." >&2
  exit 1
fi

echo "ERROR: butuh podman atau docker di host (tidak perlu PHP/Composer/Node)." >&2
exit 1
