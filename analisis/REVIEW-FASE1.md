# REVIEW FASE 1 — QA Gate (Vera) + Verifikasi Runtime (Nova/Ray)

## 1. Ringkasan verdict

| Area | Verdict | Catatan |
|---|---|---|
| T1 Isolasi `church_id` | ✅ **APPROVED** | Guard + scope query + widget dashboard; 5 test tenant hijau. |
| T2 RBAC + Policy | ✅ **APPROVED** | 14 Policy, System/User/Official khusus super_admin; test escalation hijau. |
| T3 Anti privilege escalation | ✅ **APPROVED** | `UserObserver` + validasi server-side; test hijau. |
| T4 Crash Warta | ✅ **APPROVED** | Blade null-safe, `minister_name` bersih, guard birth_date; 3 test warta hijau. |
| T5 Enum keuangan | ✅ **APPROVED** | Migrasi data-fix + konsistensi debit/credit; 4 test hijau. |
| T6 Filament v5 | ✅ **APPROVED** | `BadgeColumn` = 0. (Render runtime resource belum di-automasi — di backlog B4.) |
| T7 DevOps Production | ✅ **APPROVED** | Struktur Docker/compose/DEPLOY.md benar. **Runtime podman SUDAH terverifikasi nyata (lihat §5).** |
| **KESELURUHAN** | ✅ **APPROVED (LUNAS)** | Suite hijau 29/29 + runtime container hijau. Fase 1 siap untuk owner review. |

## 2–4. (AC per task — lihat versi sebelumnya; seluruh AC kode terpenuhi)

## 5. Status runtime podman — **TERVERIFIKASI (2026-08-31)**

Verifikasi dijalankan sungguhan di sandbox (Podman 5.8.4 + podman-compose 1.6.0), stack penuh 5 service:

- `podman ps` → **5/5 container up; db, app, web = healthy** (queue & scheduler running stabil).
- Migrasi **MariaDB 10.11 nyata** (bukan SQLite) → **seluruh migrasi sukses** tanpa errno 121 (issue FK bernama `1` sudah teratasi; migrasi memakai penamaan index eksplisit).
- `db:seed --force` → sukses; terverifikasi via tinker: **users=4, churches=3**, roles `church_admin` + `super_admin` (termasuk super admin untuk owner review).
- HTTP dari host:
  - `GET /up` → **200**
  - `GET /` → **200**
  - `GET /admin` → **302 → /admin/login**
  - `GET /admin/login` → **200** (halaman login Filament ter-render)
- Image produksi `portal-gereja:app` + `portal-gereja:nginx` berhasil di-build (multi-stage, self-contained, tanpa bind-mount source).

### Bug runtime yang ditemukan & diperbaiki selama verifikasi
- **R1 (Critical, fixed)** — Container `queue` & `scheduler` start sebelum migrasi selesai → crash `SQLSTATE[42S02]: Table 'portal_gereja.cache' doesn't exist` saat DB fresh. **Fix:** entrypoint kini menunggu tabel `migrations` ada (loop `SHOW TABLES`) sebelum worker start. Setelah rebuild + recreate, queue/scheduler stabil.
- **R2 (Fixed)** — `web` sempat 502 karena nginx connect php-fpm ditolak: php-fpm baru listen SETELAH migrasi selesai (entrypoint app menjalankan migrate di foreground). Ini perilaku yang benar (aplikasi baru melayani setelah DB siap); healthcheck app diperluas menunggu DB + `php-fpm -t`. Pada DB fresh, web sehat dalam ±1 menit setelah db healthy.
- **R3 (Catatan)** — `storage:link` sempat error "link already exists" (idempotent-safe, `|| true`); tidak fatal.

### Bukti log (terekam di workspace)
- `ops-up2.log` (build + start stack), `ops-build2.log` (rebuild image setelah fix entrypoint).
- Log container `portal_app` (migrasi 19/19 DONE + `fpm is running, ready to handle connections`), `portal_queue` (worker jalan stabil setelah fix), `portal_web` (nginx serve).

## 6. Backlog pasca-APPROVE (non-blocking, wajib dijadwalkan di Fase 2)

- **B1 (Medium)** — `events.church_id` masih `nullable()` (`2026_03_07_000011_create_events_table.php:14`); inkonsisten dengan tabel lain yang `constrained()` non-null. AC-T1-08 belum 100% NOT NULL.
- **B2 (Medium)** — Rollback migrasi `2026_03_08_000004` masih `$table->foreignId('member_id')->required()->change()` — `required()` bukan method Blueprint; `migrate:rollback` untuk migrasi itu akan error. Tidak kena oleh test suite (hanya jalur rollback).
- **B3 (Medium)** — `church_id` di `member_sacraments`/`event_rosters` tetap nullable by design; rekomendasi: job cleanup orphan + jadikan NOT NULL setelah data bersih.
- **B4 (Low)** — Belum ada test render resource Filament (runtime Livewire) & test finance_admin (hanya church_admin + super_admin yang dites).
- **B5 (Low)** — Healthcheck app hanya cek DB port + `php-fpm -t`, belum cek HTTP endpoint; bisa diperkuat dengan memanggil `php artisan about` atau HTTP check di fase berikutnya.

## 7. Kesimpulan gate

- **Suite:** hijau penuh (**29 test, 88 assertions, 0 failure/error/risky**) — dijalankan ulang setelah verifikasi runtime.
- **Runtime:** 5/5 container healthy, migrasi MariaDB sukses, seed sukses (4 user / 3 gereja), `/up`=200, `/admin/login`=200.
- **Verdict: APPROVED (LUNAS).** Verifikasi runtime podman yang tadinya memblokir sudah tuntas; aplikasi **siap untuk owner review** dan Fase 2 boleh dibuka setelah owner menyetujui.
