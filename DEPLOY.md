# DEPLOY — 100% Container (Podman / Docker)

Host **tetap bersih**: cukup pasang **Podman** (atau Docker). Tidak perlu
install PHP, Composer, Node, atau MariaDB di host — semuanya berjalan di dalam
container.

---

## 1) Prasyarat

```bash
podman --version        # atau: docker --version
podman compose version  # atau plugin docker compose
```

> Tidak punya podman-compose? `podman-compose` juga didukung otomatis.

## 2) Quickstart (pertama kali)

```bash
cd portal-gereja

make env        # buat .env dari contoh + generate APP_KEY & password DB acak
make up         # build image & jalankan db, app, queue, scheduler, web
make status     # tunggu semua container healthy
make seed       # (opsional, untuk review) data demo + super admin
```

Lalu buka: **http://localhost:8000**

- Login review (hasil seeder): `superadmin@gereja.test` / `password`
  → **WAJIB ganti password** segera setelah login.

## 3) Perintah harian

| Perintah | Fungsi |
|---|---|
| `make status` | status + health semua container |
| `make logs` | ikuti log semua service |
| `make shell` | masuk shell container app (ada `php` & `artisan`) |
| `make artisan c='route:list'` | jalankan perintah artisan apa pun |
| `make migrate` | jalankan migrasi |
| `make seed` | isi data demo + super admin |
| `make backup` | dump DB → `backups/portal-YYYYMMDD-HHMMSS.sql` |
| `make restore f=backups/xxx.sql` | restore DB dari backup |
| `make down` | hentikan service (volume data tetap tersimpan) |

> Semua perintah artisan dijalankan **di dalam container**:
> `podman compose exec app php artisan ...`

## 4) Port

- Web default: **8000** → ubah dengan `WEB_PORT` di `.env` (mis. `WEB_PORT=8080`).

## 5) Keamanan ringkas

- `.env` tidak pernah di-commit (sudah di-gitignore); isi APP_KEY, DB_PASSWORD,
  dan MARIADB_ROOT_PASSWORD di-generate acak oleh `make env`.
- Root password DB **tidak** diteruskan ke container aplikasi.
- Image tidak berisi `.env`/secret (`.dockerignore` mengecualikannya).
- Untuk internet: letakkan di belakang reverse-proxy TLS (Caddy/nginx/traefik),
  jangan expose port 8000 langsung ke publik. Aktifkan `SESSION_SECURE_COOKIE=true`.

## 6) Update aplikasi

```bash
git pull
make up        # rebuild image + restart, migrasi otomatis di entrypoint
```

## 7) Troubleshooting

- **Container `app` restart berulang / menunggu DB**: tunggu beberapa saat —
  entrypoint menunggu database siap sebelum migrate (maks 60 detik).
- **Port 8000 sudah terpakai**: set `WEB_PORT=8080` di `.env`, lalu `make up`.
- **Ingin reset total** (hapus volume): `podman compose down -v`
  (hati-hati: menghapus semua data).
