# 📝 T7 — DevOps Production — Change Log

**Task:** T7 (Secrets, Env & Docker Production) · Wave 1 Fase 1
**Owner:** Ray (DevOps) · Review: Vera (QA Gate)
**Status:** ✅ Dikerjakan — menunggu review QA (T8)
**Revisi awal:** `59380e2` · **Revisi akhir:** setelah commit T7 (lihat git log)

---

## 1. Ringkasan Perubahan

| # | File | Perubahan | Alasan |
|---|------|-----------|--------|
| 1 | `.env.example` | Template produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY=` kosong + komentar **WAJIB diisi**, DB mysql (bukan sqlite), placeholder `DB_PASSWORD`/`MARIADB_ROOT_PASSWORD`, `LOG_STACK=daily`, `SESSION_SECURE_COOKIE` | Menghapus default dev (`local`/`true`) yang berbahaya jika ter-copy ke prod; sinkron dengan docker-compose (mysql); memaksa operator mengisi secret |
| 2 | `.env` (baru, **tidak di-commit**) | Berisi secret hasil **rotasi**: `APP_KEY` baru (base64 32B), `DB_PASSWORD` baru (48 hex), `MARIADB_ROOT_PASSWORD` baru (64 hex) | Nilai lama (`PasswordGereja123!`, `RootSecretPassword123!`) sudah ter-expose di git history → **wajib rotasi**. Nilai baru dihasilkan acak via `secrets.token_hex` |
| 3 | `Dockerfile` | Ditulis ulang **multi-stage** (builder → app → nginx): `composer install --no-dev` + `npm ci && npm run build` di builder; runtime PHP-FPM hanya library + ekstensi hasil salin; stage nginx berisi `public/` + config | Sebelumnya image kosong (tanpa composer/npm build, vendor & build gitignored) → `docker compose up` pasti gagal. Sekarang self-contained & reproducible |
| 4 | `docker/entrypoint.sh` (baru) | chown storage, validasi/generate `APP_KEY`, tunggu DB siap, `migrate --force`, `storage:link`, `optimize` (produksi) | Menjamin app boot dengan APP_KEY, DB termigrasi otomatis, storage link ada, cache produksi ter-build saat start |
| 5 | `docker-compose.yml` | Secret dipindah ke `.env` + interpolasi `${VAR:?}` (gagal jika kosong); tambah service **queue** (queue:work) & **scheduler** (schedule:work); healthcheck semua service; `depends_on` pakai `service_healthy`; volume `portal_storage`; **hapus bind-mount source** | Menutup K6 (secret hardcode), K7 (Docker tidak jalan), H1 (tanpa healthcheck), H2 (queue/scheduler mati); self-contained + persistensi storage |
| 6 | `nginx.conf` | `server_tokens off`, timeout aman, `client_max_body_size 20m`, security headers (X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy), blokir dotfile & file sensitif, cache `/build/` immutable, `fastcgi_hide_header X-Powered-By` | Hardening reverse proxy; mencegah upload 413 & informasi versi bocor |
| 7 | `.gitignore` | Pola `.env.*` + `!.env.example`; tambah `.env.local` | Memastikan SEMUA varian .env tidak pernah ter-commit |
| 8 | `.dockerignore` (baru) | Kecualikan `.git`, `node_modules`, `vendor`, `.env*`, artefak storage, `analisis` | Mencegah secret/artefak lokal ikut context build & image |

---

## 2. Cara Secret Diamankan

1. **Dihapus dari file ter-commit** — `docker-compose.yml` tidak lagi memuat `DB_PASSWORD=PasswordGereja123!` / `MARIADB_ROOT_PASSWORD=RootSecretPassword123!`. Nilai hanya lewat interpolasi `.env` (host).
2. **Rotasi** — nilai lama dianggap bocor (terbukti di `initial commit`). Nilai baru di-generate acak di `.env` lokal:
   - `APP_KEY` = `base64:` + 32 byte acak
   - `DB_PASSWORD` / `MARIADB_ROOT_PASSWORD` = hex acak (48/64 char)
3. **`.env` tidak pernah di-commit** — diverifikasi `git check-ignore .env` → ter-ignore.
4. **`.env.example` aman** — hanya placeholder kosong + komentar instruksi; tidak ada nilai asli.
5. **`MARIADB_ROOT_PASSWORD` tidak diteruskan ke container aplikasi** — di-override `""` pada service app/queue/scheduler (hanya service db yang menerima).
6. **Tidak ada secret di image** — `.dockerignore` mengecualikan `.env*`; build pakai dummy APP_KEY hanya untuk artisan saat build (tidak masuk stage runtime).

---

## 3. Verifikasi (tanpa menjalankan docker — docker tidak tersedia di sandbox)

- [x] `git grep -E 'PasswordGereja123|RootSecretPassword123'` di working tree → **tidak ada** (sebelumnya ada di `docker-compose.yml:36,57,58`).
- [x] `git check-ignore -v .env` → ter-ignore oleh `.gitignore:8`.
- [x] YAML `docker-compose.yml` valid (PyYAML) → services: db, app, queue, scheduler, web; volumes: portal_db_data, portal_storage.
- [x] Route health `/up` ada di `bootstrap/app.php:11` (dipakai healthcheck web).
- [x] `APP_KEY` wajib: compose memakai `${APP_KEY:?...}` → `docker compose up` **gagal dengan pesan jelas** jika APP_KEY kosong.
- [x] `APP_DEBUG=false`, `APP_ENV=production` di `.env.example` & `.env`.
- [ ] ⏳ **Belum bisa dijalankan di sandbox:** `docker compose up --build` (docker tidak terpasang), `composer audit`, `npm audit`, `nginx -t` → **wajib diverifikasi Vera/CI di environment dengan Docker** (lihat langkah di bawah).

---

## 4. Langkah Verifikasi `docker compose up` dari Repo Bersih (deskriptif)

1. **Clone bersih** → pastikan tidak ada `vendor/`, `node_modules/`, `public/build`, `.env`.
2. **Siapkan env:**
   ```bash
   cp .env.example .env
   php artisan key:generate        # atau isi APP_KEY manual
   # isi DB_PASSWORD & MARIADB_ROOT_PASSWORD dengan nilai kuat (contoh sudah ada di .env lokal)
   ```
3. **Build & up:**
   ```bash
   docker compose up -d --build
   ```
   - Stage builder: `composer install --no-dev` + `npm ci && npm run build` → image `portal-gereja:app`.
   - Stage nginx: `portal-gereja:nginx` berisi `public/` + `nginx.conf`.
4. **Urutan start (dijamin healthcheck):**
   - `db` → healthy (`mariadb-admin ping`).
   - `app` → entrypoint: chown storage → validasi APP_KEY → tunggu DB → `migrate --force` → `storage:link` → `optimize` → php-fpm.
   - `queue` → `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
   - `scheduler` → `php artisan schedule:work`.
   - `web` → healthcheck `GET /up` (nginx → fpm → Laravel → DB) → healthy.
5. **Cek hasil:**
   ```bash
   docker compose ps                  # semua healthy/running
   curl -i http://localhost:8000/up   # 200 OK
   docker compose exec app php artisan migrate:status
   docker compose logs app | grep entrypoint
   ```
6. **Data persist:** `portal_db_data` (DB) & `portal_storage` (upload/storage) adalah named volume → aman saat `down`/`up` ulang.

---

## 5. Catatan & Risiko Tersisa (untuk Fase 2 / perlu aksi owner)

- ⚠️ **Git history masih memuat secret lama** (`f1d9bdd`). Rotasi sudah dilakukan, tetapi untuk pembersihan menyeluruh: `git filter-repo --replace-text` + **force push** (butuh akses remote GitHub) — koordinasi dengan Nova/owner.
- ⚠️ **Seeder default password `'password'`** untuk akun admin (temuan H4 review) — di luar scope T7 (backend), perlu perbaikan di T2/T3.
- ⚠️ **`filament:upgrade` di post-autoload-dump** dijalankan saat build — jika Filament v5 belum punya command tsb, build akan gagal; perlu diverifikasi saat build pertama.
- ⚠️ **Belum ada CI/CD** (T7 tidak mencakup; direncanakan lanjutan).
- ⚠️ **TLS/HTTPS** belum di nginx (di-routing via NPM/reverse proxy eksternal) — security headers siap.
