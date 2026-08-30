# DevOps Review — portal-gereja (Laravel 12 + Filament 5)

- **Direview:** `Dockerfile`, `docker-compose.yml`, `nginx.conf`, `.env.example`, `.gitignore`, `composer.json`, `package.json`, `boost.json`, `config/*`, `bootstrap/app.php`, `.github/`, seeder, migrations, git history.
- **Revisi:** `59380e2` (3 commit: initial → integrasi pelayan → laporan rapat/keuangan).
- **Verdict:** ⚠️ **BELUM PRODUCTION-READY** — ada 5 Critical yang memblokir `docker compose up` langsung jalan.

## Asumsi eksplisit
1. Deploy di belakang **Nginx Proxy Manager (NPM)** yang menangani TLS; container nginx internal cukup `listen 80` (sesuai komentar di compose).
2. Target server **1GB RAM / 2 vCPU** (dari AGENTS.md) — tuning MariaDB sudah disesuaikan.
3. Tidak ada kebutuhan HA/scale; single instance cukup.
4. `.env` production dibuat manual/secret manager, **bukan** copy mentah `.env.example`.
5. DB diinisialisasi fresh; migrasi + seed diharapkan berjalan otomatis saat deploy (saat ini belum ada).

---

## 🔴 Critical

- **C1 — Kredensial DB hardcoded & ter-commit** — `docker-compose.yml:36,57-58`: `DB_PASSWORD=PasswordGereja123!`, `MARIADB_PASSWORD`, `MARIADB_ROOT_PASSWORD=RootSecretPassword123!`. Terbukti ada sejak `initial commit` (`git log -S`). → Rotasi password, hapus dari history (`git filter-repo`), pindah ke `.env` (gitignored)/Docker secrets, gunakan `${VAR:?}`.
- **C2 — APP_KEY kosong, tidak ada generasi otomatis** — `.env.example:5` `APP_KEY=`; Dockerfile & compose tidak generate → Laravel fatal *"No application encryption key"* (config/app.php:100). → Entrypoint `php artisan key:generate` bila kosong (atau set eksplisit di env).
- **C3 — APP_ENV=local & APP_DEBUG=true** — `.env.example:3-4` → jika di-copy ke prod: stack trace, config, kredensial bocor. → Set `APP_ENV=production`, `APP_DEBUG=false` di deploy.
- **C4 — Image tidak self-contained, compose up langsung gagal** — Dockerfile hanya install ekstensi PHP; tidak ada `composer install`, `npm ci && npm run build`, copy source. `vendor/` & `public/build` di-gitignore; compose hanya bind mount `./:/var/www/html` (docker-compose.yml:14,31). Fresh clone → tanpa vendor & aset → 500. → **Multi-stage build** (composer install --no-dev, vite build, artisan optimize) + copy ke image.
- **C5 — Tidak ada entrypoint: migrasi, seeder, storage:link** — tidak ada `php artisan migrate --force --seed` maupun `storage:link`; volume DB baru = tabel kosong; `public/storage` symlink tidak ada (cek langsung: tidak ada). → Entrypoint script: `migrate --force --seed` (sekali) + `storage:link` + `optimize`.

## 🟠 High

- **H1 — Tanpa healthcheck** — semua service (web/app/db) tidak punya `healthcheck`; `depends_on` tanpa `condition: service_healthy` → race condition (nginx up sebelum app siap). Padahal endpoint `/up` sudah tersedia (bootstrap/app.php:12) → tinggal dipakai: `curl -f http://app/up`, `mariadb-admin ping`.
- **H2 — Queue worker & scheduler tidak dijalankan** — `QUEUE_CONNECTION=database` (.env.example:45) + tabel jobs ada, tapi tidak ada service worker (`php artisan queue:work`) & tidak ada schedule di routes/console.php. Export Excel (routes/web.php) & job apa pun tidak akan pernah diproses. → Tambah service `queue` + jalankan `schedule:work` atau cron `schedule:run`.
- **H3 — Nginx tanpa hardening** — `nginx.conf`: tanpa security headers (X-Content-Type-Options, X-Frame-Options, CSP, Referrer-Policy), tanpa gzip, tanpa cache-control untuk aset, `client_max_body_size` default 1MB → upload Filament >1MB = 413. → Tambahkan hardening + `client_max_body_size 20m` + cache asset (immutable).
- **H4 — Seeder password default `'password'`** — `database/seeders/DatabaseSeeder.php:57,65,73` (+ super admin) → jika di-seed ke prod = akun lemah. → Password acak/kuat via env, atau jangan seed di prod.
- **H5 — `composer:latest` unpinned & tanpa `.dockerignore`** — Dockerfile:14 `composer:latest` → build non-reproducible; tidak ada `.dockerignore` → build context membawa `.git`, `node_modules`, dsb. → Pin `composer:2`, tambah `.dockerignore`.

## 🟡 Medium

- **M1 — Bind mount seluruh repo** (`./:/var/www/html`, compose:14,31) → kode+storage+upload campur; ownership `www-data` vs host bisa memicu 403/500 di `storage/framework`, `storage/logs`, `bootstrap/cache`. → Named volume untuk `storage` + chown di entrypoint.
- **M2 — Mismatch `.env.example` vs compose** — `.env.example:13` `DB_CONNECTION=sqlite`, compose pakai MySQL/MariaDB → copy .env.example mentah = salah koneksi. → Jadikan `.env.example` default MySQL sesuai stack, atau pisahkan `.env.production`.
- **M3 — `SESSION_SECURE_COOKIE` tidak diset** — config/session.php:172 default null → cookie bisa terkirim via HTTP bila user akses non-HTTPS (di belakang NPM, pastikan redirect HTTPS). → Set `SESSION_SECURE_COOKIE=true` di prod.
- **M4 — Log tanpa rotasi & persist** — `LOG_STACK=single`, `LOG_LEVEL=debug` → file membesar, ownership campur. → `LOG_CHANNEL=daily`, `LOG_LEVEL=info`, named volume logs.
- **M5 — Tanpa resource limits** — compose tidak ada `deploy.resources.limits` → risiko OOM di VPS 1GB (DB sudah di-tuning, app/nginx belum). → Batasi memory app & web.
- **M6 — Tidak ada CI/CD** — `.github/` hanya `copilot-instructions.md`; tidak ada workflow test/lint/secret-scan/build. → Tambah GitHub Actions: `composer install`, `pint`, `phpunit`, `npm run build`, secret scan (gitleaks), build image.
- **M7 — Tidak ada strategi backup DB** — volume `portal_db_data` persist, tapi tanpa dump/backup terjadwal. → Cron `mysqldump` + offsite, atau gunakan managed DB.
- **M8 — `container_name` fixed** (compose:10,25,43) → tidak bisa scale/recreate paralel. → Hapus atau jadikan variabel (minor).

## 🟢 Low

- **L1 — `verify-seeding.php` debug script ikut ter-commit** (root project). → Pindah ke `tests/` atau hapus.
- **L2 — `.gitignore` belum umum** — hanya `.env`, `.env.backup`, `.env.production`; `.env.local`, `.env.staging` bisa lolos ter-commit. → Tambah `.env.*` + `!.env.example`.
- **L3 — Default identitas masih template** — `APP_NAME=Laravel`, `APP_URL=http://localhost` (.env.example:1,6). → Sesuaikan.
- **L4 — Versi minor ter-pin dengan `^`** (composer.json: package.json) → lock file sudah ada, aman; hanya catatan agar rutin `composer update` security.

---

## ✅ Yang sudah baik
- `.env` tidak pernah ter-track di git (hanya `.env.example`), tidak ada secret lain di tracked files.
- `composer.lock` & `package-lock.json` ada → install reproducible. Laravel v12.53.0, Filament v5.3.2, Livewire v4.2.1 — versi stabil terkini.
- MariaDB 10.11 LTS + tuning `innodb-buffer-pool-size=128M`, `max-connections=50` — cocok RAM 1GB.
- DB **tidak di-expose ke host** (tanpa `ports`) → tidak bisa diakses dari luar.
- Volume `portal_db_data` persist data.
- Konfigurasi nginx → FPM benar (`fastcgi_pass app:9000`, `root /var/www/html/public`, `try_files`).
- Endpoint health `/up` sudah ada di `bootstrap/app.php`.
- Migrasi tabel users/sessions/cache/jobs lengkap; `declare(strict_types=1)` & konvensi Laravel 12 diikuti.

---

## Rekomendasi prioritas eksekusi

**Segera (sebelum deploy):** C1 (rotasi + hapus history) → C2 (APP_KEY/entrypoint) → C3 (env prod) → C4/C5 (multi-stage + entrypoint migrate/storage:link) → H4 (seeder) → H1 (healthcheck).
**Minggu ini:** H2 (queue+scheduler), H3 (nginx hardening), H5 (.dockerignore + pin composer), M1 (named volume storage), M6 (CI workflow).
**Bulan ini:** M7 (backup DB), M3 (secure cookie/TLS check), M4 (log daily), M5 (resource limits), M8 (container_name), L1–L4 (pembersihan).

> Catatan: `composer audit` & `npm audit` tidak dapat dijalankan di runner ini (composer/php tidak terpasang) — jalankan sebelum deploy untuk validasi CVE dependency.
