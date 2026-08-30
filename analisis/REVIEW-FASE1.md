# REVIEW FASE 1 — QA Gate (Vera)

> **Repo yang direview:** `~/.hermes/workspace-main/portal-gereja/` (repo bersama)
> **HEAD:** `8c9b508` (T1-followup) — diverifikasi via `git rev-parse HEAD` ✅
> **Cakupan review ini:** commit `8c9b508` (T1-followup) + commit `c270cea` (ops container-only)
> **Metode:** inspeksi statis (baca kode + grep + git diff). Server tidak dijalankan.
> **Catatan proses:** sandbox QA (`workspace-vera/portal-gereja`) TERTINGGAL di `7cf3828` dan TIDAK dipakai; seluruh review memakai path repo bersama.

---

## Ringkasan Verdict

| Item | Commit | Verdict |
|---|---|---|
| 1a. Trait `BelongsToChurch` — paksa church_id aktor (anti mass-assignment) | `8c9b508` | ✅ **APPROVED** |
| 1b. Migrasi backfill portabel SQLite/MySQL | `8c9b508` | ✅ **APPROVED** (dengan catatan performa & gap AC NOT NULL) |
| 2. Ops workflow container-only (`/up`, Makefile, setup-env, DEPLOY.md, compose podman) | `c270cea` | ✅ **APPROVED** (dengan catatan non-blocking) |

Tidak ada item BLOCKED. Namun ada **temuan residual non-blocking** yang wajib dijadwalkan sebelum owner sign-off (lihat §5).

---

## 1) Commit `8c9b508` — T1-followup

### 1a. `app/Traits/BelongsToChurch.php` — paksa church_id aktor → ✅ APPROVED

Perubahan (baris 32–44):
```php
static::creating(function ($model) {
    $actor = auth()->user();
    if ($actor && $actor->role !== 'super_admin') {
        $model->church_id = $actor->church_id;      // baris 37-38
    }
    if (empty($model->church_id) && $actor) {
        $model->church_id = $actor->church_id;      // baris 42-43
    }
});
```

**Apakah benar menutup celah?**
- Ya. Untuk non-`super_admin`, `church_id` **selalu ditimpa** dengan `church_id` aktor pada event `creating` — terlepas dari apa pun yang dikirim via mass-assignment/crafted POST (mis. `church_id=GerejaB`). Ini menutup K1/AC-T1-08 di lapisan model (defense-in-depth terakhir setelah form & policy).

**Dampak samping yang dicek:**
- **Import/seed tanpa auth** (CLI, seeder, queue, tinker): `auth()->user()` = null → kedua `if` tidak dieksekusi → `church_id` tetap sesuai nilai eksplisit (atau null). Tidak ada regresi vs perilaku lama. ✅
- **super_admin assign ke gereja lain**: `if` pertama di-skip (role = super_admin) → nilai `church_id` eksplisit dipertahankan. Super admin tetap bisa membuat record untuk gereja mana pun. ✅
- **super_admin tanpa church_id eksplisit**: fallback `empty() && $actor` mengisi ke `church_id` milik super_admin sendiri (bisa null → tetap null). Perilaku ini SAMA dengan kode lama, bukan regresi.
- **Global scope** (baris 27) tidak berubah → konsisten dengan AC-T1-04.

**Temuan residual (non-blocking):**
- 🟡 **Tidak ada guard di event `updating`** — non-super_admin masih bisa memindahkan record milik gereja sendiri ke gereja lain via update (data integrity; bukan leak lintas-gereja karena global scope membatasi query). Rekomendasi: tambahkan hook `updating` simetris (baris ~46) untuk menutup jalur update.
- 🟡 **Aktor non-super_admin dengan `church_id = null`** (user yatim hasil bug C5 di masa lalu): record baru akan dibuat dengan `church_id = null` → yatim/tidak terlihat siapa pun. Aman (fails-closed) tapi sebaiknya `abort`/tolak daripada diam-diam membuat orphan. Sebagian sudah dicegah oleh `UserPolicy`/`UserObserver` (T3).

### 1b. Migrasi `2026_03_09_000001` — backfill portabel → ✅ APPROVED

**Kebenaran logika (baris 35–42 & 52–59):**
- Hanya memproses baris `whereNull('church_id')` (semua baris saat migrasi pertama dijalankan; idempotent jika diulang).
- `member_sacraments.church_id` ← `members.church_id` via `member_id`; `event_rosters.church_id` ← `events.church_id` via `event_id`. Sesuai AC-T1-02.
- Baris orphan (parent null/tidak ditemukan) dibiarkan NULL = "unknown" → tidak tampil di gereja mana pun. **Tidak ada data dipindah/dihapus.** ✅
- Portabel MySQL + SQLite (`:memory:` untuk test). ✅

**Performa (catatan, bukan bug):**
- ⚠️ Pola N+1: per baris sakramen/roster → 1 query ke `members`/`events` (baris 38, 55), plus `->get()` memuat seluruh tabel ke memori (baris 35, 52). Untuk dataset besar (mis. >50rb baris) bisa lambat/makan memori. Rekomendasi: preload map `DB::table('members')->pluck('church_id','id')` sekali + `chunkById`/`cursor`. Untuk skala gereja tipikal (ribuan) acceptable.

**Gap vs AC (sudah ada sejak 7f69313, bukan regresi commit ini):**
- 🟡 **AC-T1-01 tidak terpenuhi penuh**: tidak ada migrasi lanjutan yang mengubah `church_id` menjadi **NOT NULL** pada `member_sacraments` & `event_rosters` (tetap `nullable()` baris 31, 48). Changelog menyatakan orphan sengaja dibiarkan NULL — keputusan defensif, tapi menyimpang dari AC. Rekomendasi: tambah migrasi backfill-orphan/cleanup lalu `nullable(false)` + index, ATAU amend AC secara eksplisit.
- 🟡 **AC-T1-08 (events.church_id NOT NULL) belum ada** — migrasi `2026_03_07_000011` masih `nullable()`. Bukan bagian commit ini, tapi tetap terbuka.

---

## 2) Commit `c270cea` — ops: workflow container-only → ✅ APPROVED

**Yang dicek:**
- `routes/web.php` → `GET /up` (baris 6-8) untuk healthcheck nginx. Publik tanpa auth — aman (hanya string `ok`), tidak membocorkan data; konsisten dengan healthcheck `web` (`wget http://127.0.0.1/up`, compose baris 142-147). ✅
- `Makefile` + `scripts/compose.sh` → auto-select podman/podman-compose/docker compose; host tidak perlu PHP/Composer/Node. ✅
- `scripts/setup-env.sh` → generate `APP_KEY` (base64 32 byte — format Laravel benar), `DB_PASSWORD`, `MARIADB_ROOT_PASSWORD` via `openssl`; tidak menimpa nilai yang sudah ada. ✅
- `DEPLOY.md` → dokumentasi cukup jelas (prasyarat, quickstart, perintah harian, port, keamanan, update, troubleshooting). ✅
- `docker-compose.yml` → service `db/app/queue/scheduler/web` + healthcheck + volume `portal_db_data`/`portal_storage` + network `portal_network` — **tidak ada konflik port/volume/service** dengan `013d1fc` (port web tetap 8000 default, kini `WEB_PORT` configurable baris 139; target Dockerfile `app`/`nginx` masih valid). Root password tidak diteruskan ke container aplikasi (baris 61, 94, 119). ✅

**Catatan non-blocking:**
- 🟡 **Guard `:?` dihilangkan** (sebelumnya `DB_PASSWORD:?`, `MARIADB_ROOT_PASSWORD:?`, `APP_KEY:?` → kini variabel polos, baris 27-28, 53, 87, 112). Ini melemahkan fail-fast bila seseorang menjalankan `docker compose up` TANPA `make env` (secret kosong diam-diam). Alasan: kompatibilitas podman-compose yang tidak mendukung interpolasi `:?`. Rekomendasi: tambahkan preflight check di `scripts/compose.sh` (validasi `APP_KEY`, `DB_PASSWORD`, `MARIADB_ROOT_PASSWORD` non-kosong di `.env` sebelum `up`) — menjaga keamanan + kompatibilitas.
- 🟡 **`depends_on.condition: service_healthy`** (baris 44-47, 76-79, 100-103, 132-135) didukung `docker compose` & `podman compose`, tapi **podman-compose (Python) punya dukungan terbatas** untuk `condition` ini — klaim "kompatibel podman-compose" perlu diverifikasi runtime. Safety net sudah ada: entrypoint menunggu DB ≤60 detik, jadi walau ordering diabaikan, app tetap menunggu DB. Non-blocking.
- 🟡 **`setup-env.sh`**: `sed -i` GNU-only (gagal di macOS); dan `.env` tidak di-`chmod 600` (default 644 — terbaca user lain di host shared). Rekomendasi: tambah `chmod 600 .env` di akhir script.
- 🟡 **Healthcheck `db`** memakai `-p$$MARIADB_ROOT_PASSWORD` dalam CMD-SHELL (baris 32) — aman selama password hasil generator base64 (tanpa spasi/metachar); bila user set password manual dengan karakter spesial, healthcheck bisa gagal. Catatan minor.

---

## 3) Regresi & interaksi antar perubahan

- **Trait baru vs global scope T1**: konsisten — scope membaca `role !== 'super_admin'`, hook `creating` membaca `role` sama; tidak ada konflik. ✅
- **Trait vs factory/seed tanpa auth**: tidak terdampak (kedua `if` butuh `auth()->user()`). ✅
- **Compose c270cea vs Dockerfile/entrypoint 013d1fc**: target stage `app`/`nginx`, entrypoint `docker/entrypoint.sh`, `nginx.conf` root, `.dockerignore` (mengecualikan `.env`, `*.md`, `analisis`, `vendor`, `public/build`) — semua masih cocok. ✅
- **`/up` route vs middleware**: berada di `routes/web.php` (group `web`), tanpa `auth` — disengaja untuk liveness; tidak bentrok dengan route lain. ✅
- **UserObserver & EventFactory (uncommitted)**: bukan bagian 2 commit ini, tapi terlihat memperbaiki self-edit role & `end_datetime` factory. Perlu di-commit (lihat §5).

---

## 4) Verdict AC terkait (ringkas)

| AC | Status |
|---|---|
| AC-T1-02 (backfill dari relasi) | ✅ terpenuhi (portabel) |
| AC-T1-03 (trait di 2 model) | ✅ terpenuhi (sudah sejak 7f69313) |
| AC-T1-04 (scope & auto-fill, super_admin bebas) | ✅ terpenuhi |
| AC-T1-08 (anti crafted POST lintas gereja) | ✅ diperkuat (forcing di `creating`) — namun events.church_id NOT NULL masih terbuka 🟡 |
| AC-T1-01 (NOT NULL setelah backfill) | 🟡 belum — tetap nullable (keputusan defensif, perlu amend AC/migrasi lanjutan) |
| AC-T7-01..06 (DevOps) | ✅ tidak diregresi oleh c270cea; guard `:?` dihilangkan 🟡 |

---

## 5) Temuan residual yang wajib dijadwalkan (non-blocking untuk 2 commit ini)

**Prioritas tinggi (sebelum owner sign-off):**
1. **Migrasi NOT NULL `church_id`** untuk `member_sacraments` & `event_rosters` (AC-T1-01) + backfill/cleanup orphan, ATAU amend AC. — `database/migrations/2026_03_09_000001`
2. **Migrasi `events.church_id` NOT NULL** (AC-T1-08) + backfill baris NULL. — `2026_03_07_000011`
3. **Guard `updating` di trait `BelongsToChurch`** (tutup jalur update pindah gereja). — `app/Traits/BelongsToChurch.php:46+`
4. **Preflight secret di `scripts/compose.sh`** (ganti guard `:?` yang dihapus). — `docker-compose.yml:27-28,53,87,112`

**Prioritas sedang:**
5. Optimasi backfill (preload map + chunk) untuk dataset besar. — migrasi `2026_03_09_000001:35-59`
6. `chmod 600 .env` di `setup-env.sh`; catat keterbatasan `sed -i` GNU.
7. Verifikasi runtime podman-compose: `depends_on.condition: service_healthy` & healthcheck.
8. **Uncommitted working tree**: `app/Models/Member.php` (import `AsArrayObject`), `app/Observers/UserObserver.php` (fix self-edit role), `database/factories/EventFactory.php` (fix end_datetime) — **belum di-commit**. Segera commit agar state repo bersih & reviewable.

---

*Ditulis oleh Vera (QA Gate). Verdict akhir 2 commit: APPROVED — tidak ada BLOCKED. Temuan residual di atas bukan penghambat merge commit ini, tapi wajib masuk backlog sebelum deklarasi siap ke owner.*
