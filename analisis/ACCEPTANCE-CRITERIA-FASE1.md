# ✅ Acceptance Criteria — Fase 1 (Wave 1: T1 & T7)

**Project:** portal-gereja (Laravel 12 + Filament 5 + PHP 8.4)
**Wave 1:** T1 Isolasi `church_id` (Byte) · T7 DevOps Production (Ray)
**Spec/BA:** Ada · **Review/QA:** Vera · **Koordinasi:** Nova
**Dasar:** `analisis/PLAN-FASE-1.md` & `analisis/HASIL-RAPAT.md` (temuan K1–K12)
**Cara verifikasi:** Baca kode (tanpa menjalankan server) — kecuali AC-T7-06 (smoke `docker compose up`) yang butuh runtime.

---

## 1) T1 — Isolasi `church_id` (Epik A)

### AC-T1-01 · Migrasi skema: `church_id` di `member_sacraments` & `event_rosters`
- **GIVEN** repositori sebelum T1,
  **WHEN** reviewer membaca migrasi baru di `database/migrations/`,
  **THEN** ada migrasi yang menambahkan kolom `church_id` (nullable, `foreignId()->constrained('churches')`) ke tabel `member_sacraments` **dan** `event_rosters`, lalu migrasi lanjutan yang mengubahnya menjadi **NOT NULL** + `->index()` setelah backfill.

### AC-T1-02 · Backfill dari relasi (bukan NULL/hardcode)
- **GIVEN** data lama yang sudah ada,
  **WHEN** membaca logika migrasi backfill,
  **THEN** `member_sacraments.church_id` diisi dari `members.church_id` (join via `member_id`) dan `event_rosters.church_id` diisi dari `events.church_id` (join via `event_id`) — tidak boleh `UPDATE ... SET church_id = NULL` atau nilai statis.

### AC-T1-03 · Model memakai `BelongsToChurch`
- **GIVEN** file `app/Models/MemberSacrament.php` dan `app/Models/EventRoster.php`,
  **WHEN** reviewer membaca class-nya,
  **THEN** keduanya menggunakan trait `App\Traits\BelongsToChurch` (`use BelongsToChurch, HasFactory;`) sehingga global scope `where('church_id', auth()->user()->church_id)` aktif untuk non-`super_admin`.

### AC-T1-04 · Global scope tidak mengecualikan super_admin
- **GIVEN** trait `app/Traits/BelongsToChurch.php`,
  **WHEN** reviewer membaca `bootBelongsToChurch()`,
  **THEN** global scope hanya menambahkan filter jika `auth()->user()->role !== 'super_admin'` — super_admin tetap melihat semua gereja, dan `creating` tetap auto-assign `church_id` dari user login jika kosong.

### AC-T1-05 · Widget dashboard ter-scope
- **GIVEN** `app/Filament/Widgets/StatsOverview.php`,
  **WHEN** admin gereja A login lalu membuka dashboard,
  **THEN** query `Member::where('status','aktif')->count()` dan `Transaction::where('type', ...)` **tidak** boleh memuat data gereja lain — harus ter-scope via global scope model **atau** `where('church_id', auth()->user()->church_id)` eksplisit.
- **GIVEN** `app/Filament/Widgets/CashFlowChart.php`,
  **WHEN** admin gereja A login,
  **THEN** `Transaction::whereYear('transaction_date', $year)` juga ter-scope `church_id` (global scope/eksplisit) sehingga grafik hanya arus kas gereja A.

### AC-T1-06 · Query sakramen & roster di resource ter-scope
- **GIVEN** `SacramentsRelationManager` (RelationManager di MemberResource),
  **WHEN** admin gereja A membuka tab Sakramen,
  **THEN** data `MemberSacrament` difilter `church_id` A (via global scope), dan pilihan `official_id` dibatasi `where('church_id', auth()->user()->church_id)` (sudah ada — harus tetap ada).
- **GIVEN** roster `Repeater` di `EventResource`,
  **WHEN** admin gereja A mengelola petugas acara,
  **THEN** pilihan `member_id`, `official_id`, `role_id` tetap difilter `church_id` A, dan `EventRoster` yang tersimpan ikut ter-scope via global scope.

### AC-T1-07 · Laporan Warta Jemaat ter-scope
- **GIVEN** `app/Filament/Clusters/Reporting/Pages/WartaJemaat.php` → `getReportData()`,
  **WHEN** admin gereja A membuka halaman Warta,
  **THEN** keempat query (`Event` + rosters, `Member` ulang tahun, `MemberSacrament`, `Transaction`) semuanya ter-scope `church_id` A — via global scope model atau `where('church_id', ...)` eksplisit; roster yang dimuat hanya milik event gereja A.

### AC-T1-08 · Referential integrity cross-church (anti crafted POST)
- **GIVEN** request buatan (crafted POST) dari admin gereja A yang mengirim `member_id`/`official_id`/`role_id` milik gereja B,
  **WHEN** data diproses (create/update `MemberSacrament` atau `EventRoster`),
  **THEN** ditolak oleh validasi server-side — minimal salah satu dari: `Rule::exists('members','id')->where('church_id', auth()->user()->church_id)` pada form/request, **atau** hook `creating`/observer yang membandingkan `church_id` induk dengan `church_id` user — dan record **tidak tersimpan** ke DB.
- **GIVEN** `events.church_id` (migrasi `2026_03_07_000011` saat ini `nullable`),
  **WHEN** reviewer membaca migrasi baru/perbaikan,
  **THEN** ada migrasi yang mengubah `events.church_id` menjadi **NOT NULL** + backfill untuk baris NULL (konsisten dengan tabel lain).

### AC-T1-09 · Accept utama isolasi
- **GIVEN** admin gereja A login,
  **WHEN** menjalankan query apa pun terhadap model ber-trait `BelongsToChurch` (Member, Family, Transaction, Event, Fund, MemberSacrament, EventRoster, dst.),
  **THEN** hasil query **tidak pernah** memuat baris dengan `church_id != church_id(A)` — dari mana pun jalurnya (resource, widget, laporan, tinker, controller).

---

## 2) T7 — DevOps & Production Readiness (Epik C)

### AC-T7-01 · Secrets tidak boleh ter-commit
- **GIVEN** seluruh file ter-commit di repo (termasuk `docker-compose.yml`, `.env.example`, `Dockerfile`, `nginx.conf`),
  **WHEN** dilakukan `grep -rni` untuk `PasswordGereja123!`, `RootSecretPassword123!`, atau nilai secret lain,
  **THEN** tidak ditemukan satupun (password lama harus dirotasi/dihapus dari git history — minimal dari HEAD; riwayat lama dicatat sebagai risiko terpisah).
- **GIVEN** `docker-compose.yml`,
  **WHEN** membaca environment service `app` dan `db`,
  **THEN** nilai DB password/root diambil dari variable `.env` (mis. `${DB_PASSWORD}`, `${DB_ROOT_PASSWORD}`) — bukan string hardcode.
- **GIVEN** `.env.example`,
  **WHEN** dibaca,
  **THEN** berisi placeholder kosong (bukan nilai asli) + komentar variabel wajib; dan `.env` tetap ada di `.gitignore`.

### AC-T7-02 · APP_KEY & konfigurasi production
- **GIVEN** `.env` production,
  **WHEN** reviewer membaca nilainya,
  **THEN** `APP_KEY` terisi (32-byte base64, hasil `php artisan key:generate`), `APP_ENV=production`, `APP_DEBUG=false`, dan `DB_*` mengarah ke service database container.
- **GIVEN** `APP_DEBUG=false`,
  **WHEN** aplikasi boot di production,
  **THEN** error tidak menampilkan stack trace/debug (verifikasi: `config/app.php` membaca `env('APP_DEBUG', false)` dan tidak ada override `'debug' => true`).

### AC-T7-03 · Dockerfile self-contained (build di dalam image)
- **GIVEN** `Dockerfile`,
  **WHEN** reviewer membacanya,
  **THEN** ada langkah: `composer install --no-dev --optimize-autoloader`, `npm ci` (atau `npm install`) lalu `npm run build`, dan hasil `public/build` + `vendor` tersalin ke image — image **tidak** bergantung volume host untuk `vendor`/`public/build`.
- **GIVEN** `vendor/` & `public/build/` ada di `.gitignore`,
  **WHEN** repo di-clone bersih lalu `docker build`,
  **THEN** build menghasilkan image yang berisi dependency & asset (diverifikasi dari Dockerfile, bukan dari volume mount).
- **GIVEN** base image,
  **WHEN** membaca `FROM` dan `COPY --from=composer`,
  **THEN** versi dipin (tag spesifik, mis. `php:8.4-fpm-alpine` + SHA, `composer:2.x`) — bukan `composer:latest` yang unpinned.
- **GIVEN** entrypoint container `app`,
  **WHEN** container start,
  **THEN** skrip entrypoint (mis. `docker/entrypoint.sh`) menjalankan `php artisan migrate --force` dan `php artisan storage:link` (idempotent) sebelum `php-fpm` start.

### AC-T7-04 · Queue worker & scheduler
- **GIVEN** `docker-compose.yml`,
  **WHEN** reviewer membaca services,
  **THEN** ada service/command `queue` yang menjalankan `php artisan queue:work` dan service/command `scheduler` yang menjalankan `php artisan schedule:work` (atau cron `schedule:run`) — keduanya dengan `QUEUE_CONNECTION=database`.
- **GIVEN** `.env` & `config/queue.php`,
  **WHEN** dibaca,
  **THEN** `QUEUE_CONNECTION=database` dan tabel `jobs`/`job_batches`/`failed_jobs` tersedia (migrasi `0001_01_01_000002_create_jobs_table.php` sudah ada — pastikan tidak dihapus).
- **GIVEN** `routes/console.php`,
  **WHEN** dibaca,
  **THEN** ada minimal satu definisi `Schedule` nyata (bukan hanya `inspire`) yang akan dieksekusi scheduler.

### AC-T7-05 · Nginx: security headers & upload size
- **GIVEN** `nginx.conf`,
  **WHEN** reviewer membaca blok `server`,
  **THEN** ada `add_header` untuk: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN` (atau `DENY`), `Referrer-Policy`, `X-XSS-Protection`, `Permissions-Policy`, dan `Content-Security-Policy` dasar.
- **GIVEN** `nginx.conf`,
  **WHEN** dibaca,
  **THEN** ada `client_max_body_size` (min. `20m`) untuk mendukung upload.

### AC-T7-06 · Accept utama DevOps (smoke test — butuh runtime)
- **GIVEN** repo bersih (clone tanpa `vendor/`, `node_modules/`, `public/build/`),
  **WHEN** `docker compose up -d --build` dijalankan,
  **THEN** aplikasi boot di port 8000, migrasi berjalan otomatis (`migrate --force`), `storage:link` terbuat, queue worker & scheduler aktif, dan halaman login Filament (`/admin/login`) merespons HTTP 200.
- **GIVEN** hasil smoke test,
  **WHEN** dicek `docker compose ps` dan log,
  **THEN** service `web`, `app`, `db`, `queue`, `scheduler` berstatus running (tanpa restart-loop).

---

## 3) Checklist Verifikasi 2-Gereja (T1 — tidak ada data bocor)

**Setup fixture:** Gereja A & B, masing-masing 1 `church_admin` + data unik: 2 Family, 2 Member, 1 Sacrament, 1 Event + 1 Roster, 1 Official, 1 Transaction, 1 Fund, 1 Category (nama data dibuat berbeda antar gereja agar mudah dideteksi).

| # | Skenario (login admin A) | Ekspektasi |
|---|---|---|
| 1 | Buka List Members / Families | Hanya data gereja A |
| 2 | Buka Dashboard (StatsOverview & CashFlowChart) | Angka/grafik hanya dari transaksi & member A |
| 3 | Buka Warta Jemaat | Event, ulang tahun, sakramen, transaksi hanya milik A |
| 4 | Buka tab Sakramen member A | Hanya sakramen A; dropdown Official hanya daftar A |
| 5 | Buka Event + roster | Hanya event & roster A; dropdown member/official/role hanya A |
| 6 | Buka Laporan Rapat | Hanya data A |
| 7 | Tinker: `MemberSacrament::all()`, `EventRoster::all()`, `Transaction::all()` | 0 baris milik B (global scope aktif) |
| 8 | Crafted POST: buat sacrament dengan `member_id` milik B | Ditolak validasi server-side, tidak tersimpan |
| 9 | Crafted POST: buat roster dengan `member_id`/`official_id`/`role_id` milik B | Ditolak validasi server-side, tidak tersimpan |
| 10 | Akses URL edit record milik B (mis. `/admin/.../B/edit`) | 404/redirect/denied (record tidak ditemukan karena scope) |
| 11 | Login admin B → ulangi #1–#10 (simetris) | Hasil simetris, tidak ada data A di B |
| 12 | Login super_admin | Bisa melihat semua gereja tanpa error (scope tidak membatasi super_admin) |

---

## 4) Lampiran — Peta Temuan K → Task & File yang Diperiksa

| Temuan (HASIL-RAPAT) | Task | File utama yang dicek |
|---|---|---|
| K1: `MemberSacrament` tanpa `church_id`; Warta & widget query tanpa scope | T1 | `app/Models/MemberSacrament.php`, `app/Filament/Clusters/Reporting/Pages/WartaJemaat.php`, `app/Filament/Widgets/*.php`, migrasi baru |
| K1: `event_rosters` tanpa `church_id` | T1 | `app/Models/EventRoster.php`, `app/Filament/Clusters/Events/Resources/Event/EventResource.php`, migrasi baru |
| High: cross-church referential integrity tidak di-enforce | T1 | Validasi form/request (`Rule::exists ... where church_id`), observer/creating hook, FK di migrasi |
| High: `events.church_id` nullable inkonsisten | T1 | Migrasi `2026_03_07_000011` + migrasi perbaikan |
| K6: secrets hardcode di git | T7 | `docker-compose.yml`, `.env.example`, `.gitignore`, grep history |
| K7: Dockerfile bukan production-ready | T7 | `Dockerfile`, entrypoint, `.dockerignore` |
| High: tidak ada queue worker/scheduler; nginx tanpa security headers; `composer:latest` unpinned | T7 | `docker-compose.yml`, `nginx.conf`, `routes/console.php`, `Dockerfile` |

---

# ✅ Acceptance Criteria — Fase 1 (Wave 2 & 3: T2–T6)

**Task:** T2 RBAC + Policy (Byte) · T3 Fix Privilege Escalation (Byte) · T4 Fix Crash Warta (Byte+Pixel) · T5 Enum Keuangan (Byte) · T6 Fix API Filament v5 (Pixel)
**Spec/BA:** Ada · **Review/QA:** Vera · **Koordinasi:** Nova
**Dasar:** `analisis/PLAN-FASE-1.md` & `analisis/HASIL-RAPAT.md` (K2, K3, K4, K7, High items)
**Cara verifikasi:** Baca kode (tanpa menjalankan server).

---

## 5) T2 — RBAC + Policy per Resource (Epik A)

### AC-T2-01 · Policy class ada & terdaftar
- **GIVEN** `app/Policies/`,
  **WHEN** reviewer membuka direktori,
  **THEN** ada policy untuk minimal: `UserPolicy`, `OfficialPolicy`, `ChurchPolicy`, `TransactionPolicy`, `EventPolicy` (direkomendasikan juga: `FamilyPolicy`, `MemberPolicy`, `FundPolicy`, `FinancialCategoryPolicy`, `EventCategoryPolicy`, `MinistryRolePolicy`) — tiap policy memiliki method `viewAny`/`view`/`create`/`update`/`delete`/`deleteAny`.
- **GIVEN** `app/Providers/AppServiceProvider.php`,
  **WHEN** dibaca,
  **THEN** policy terdaftar via `Gate::policy(Model::class, ModelPolicy::class)` **atau** auto-discovery Laravel aktif (konvensi `App\Policies\{Model}Policy`).

### AC-T2-02 · System cluster (User/Official/Church) = super_admin only
- **GIVEN** user `church_admin` atau `finance_admin` login,
  **WHEN** membuka panel admin,
  **THEN** navigasi cluster **System** (User, Official, Church) tidak muncul, dan akses langsung ke URL `/admin/system/users`, `/admin/system/officials`, `/admin/system/churches` ditolak (403/404) — diverifikasi dari: `SystemCluster::shouldRegisterNavigation()`/`canAccess()`, `UserResource::canViewAny()`, `OfficialResource::canViewAny()`, `ChurchResource::canViewAny()` yang mengecek `role === 'super_admin'`.
- **GIVEN** `super_admin` login,
  **WHEN** membuka halaman yang sama,
  **THEN** semua resource System dapat diakses.

### AC-T2-03 · finance_admin dibatasi ke modul keuangan
- **GIVEN** user `finance_admin` login,
  **WHEN** membuka panel,
  **THEN** akses **ditolak** untuk System cluster (User/Official/Church) — dan sesuai kebijakan, Reporting/Warta dibatasi; akses **diberikan** untuk Finance (Transaction) + MasterData keuangan (Fund, FinancialCategory) milik gereja sendiri.
- **GIVEN** user `church_admin` login,
  **WHEN** membuka panel,
  **THEN** akses penuh ke modul gereja sendiri (Demographics, Events, Finance, MasterData, Reporting) tetapi **ditolak** ke System cluster.

### AC-T2-04 · canAccessPanel membatasi login
- **GIVEN** `app/Models/User.php` (method `canAccessPanel`) atau closure di `AdminPanelProvider`,
  **WHEN** reviewer membaca-nya,
  **THEN** hanya user dengan `role` ∈ {`super_admin`, `church_admin`, `finance_admin`} yang boleh masuk panel `/admin`; user tanpa role / role tidak dikenal ditolak.

### AC-T2-05 · Actions & halaman menghormati policy
- **GIVEN** resource yang punya policy,
  **WHEN** reviewer membaca `recordActions`/`toolbarActions` (Create/Edit/Delete/BulkDelete) dan halaman Create/Edit/List,
  **THEN** aksi tidak dirender/ditolak saat `canCreate`/`canUpdate`/`canDelete`/`canDeleteAny` = false — bukan hanya `hidden()` di UI, tapi ada pengecekan policy (via `Resource::canXxx()` / `->authorize()`).

### AC-T2-06 · Halaman Report ikut dibatasi
- **GIVEN** `WartaJemaat` (Page) dan `LaporanRapatPage` (Page),
  **WHEN** reviewer membaca class-nya,
  **THEN** ada `public static function canAccess(): bool` yang membatasi role (mis. `super_admin` + `church_admin`; `finance_admin` dibatasi untuk laporan keuangan) — dan route export terkait ikut terproteksi, bukan hanya tersembunyi dari navigasi.

---

## 6) T3 — Fix Privilege Escalation (UserResource)

### AC-T3-01 · Validasi server-side role
- **GIVEN** crafted POST dari `church_admin` yang mengirim `role=super_admin` (atau role tidak dikenal),
  **WHEN** data diproses di `CreateUser`/`EditUser` (`mutateFormDataBeforeCreate`/`mutateFormDataBeforeSave`, atau Policy `create`/`update`),
  **THEN** ditolak (validasi/403) dan user **tidak** tersimpan dengan role terlarang — pengecekan server-side, bukan hanya `hidden()`/`options()` di UI.
- **GIVEN** `church_admin` membuat/mengedit user,
  **WHEN** data diproses,
  **THEN** role yang diizinkan hanya `church_admin` atau `finance_admin` (bukan `super_admin`).

### AC-T3-02 · church_admin tidak bisa edit/delete user gereja lain / Super Admin
- **GIVEN** `church_admin` gereja A mengirim request edit/delete ke `/admin/system/users/{id}` milik gereja B atau milik `super_admin`,
  **WHEN** record di-resolve di halaman Edit/Delete,
  **THEN** ditolak — record tidak ditemukan (404) karena query ter-scope `church_id` A **dan/atau** Policy `canUpdate`/`canDelete` = false; perubahan **tidak** tersimpan.
- **GIVEN** query tabel List untuk `church_admin`,
  **WHEN** dibaca,
  **THEN** hanya menampilkan user dengan `church_id = auth()->user()->church_id` dan **tidak** menampilkan user `role=super_admin` (meski super_admin berada di gereja yang sama).

### AC-T3-03 · church_id tidak boleh NULL / tidak bisa pindah gereja
- **GIVEN** `church_admin` membuat user tanpa memilih gereja (field `church_id` tersembunyi untuk non-super_admin),
  **WHEN** data diproses di `CreateUser`,
  **THEN** `church_id` diisi fallback ke `auth()->user()->church_id` (via `mutateFormDataBeforeCreate` atau model `creating` hook) — tidak tersimpan NULL (user yatim).
- **GIVEN** `church_admin` mengirim `church_id` milik gereja B pada create/update,
  **WHEN** data diproses,
  **THEN** ditolak (nilai diabaikan/di-reset ke gereja sendiri, atau 403).

### AC-T3-04 · Password tidak ter-dehydrate saat edit
- **GIVEN** field `password` di `UserResource::form()` pada operasi `edit`,
  **WHEN** reviewer membaca definisinya,
  **THEN** field memiliki `->dehydrated(false)` (atau `->dehydrated(fn(string $operation): bool => $operation === 'create')`) sehingga password kosong **tidak** menimpa password lama menjadi NULL.

### AC-T3-05 · DeleteAction & BulkDelete terproteksi
- **GIVEN** halaman `EditUser` dan tabel List untuk `church_admin`,
  **WHEN** reviewer membaca `DeleteAction`/`DeleteBulkAction`,
  **THEN** aksi hapus hanya bisa mengenai user dalam gereja sendiri dan **tidak** bisa menghapus `super_admin` (Policy `canDelete` menolak) — tombol tidak muncul / request ditolak.

---

## 7) T4 — Fix Crash Warta Jemaat (Epik B)

### AC-T4-01 · Roster null-safe (official tanpa member)
- **GIVEN** `resources/views/filament/pages/warta-jemaat.blade.php` pada loop `@foreach ($event->rosters as $roster)`,
  **WHEN** reviewer membaca render nama petugas,
  **THEN** tidak ada akses `$roster->member->full_name` langsung; diganti logika aman: `$roster->member` ada → `$roster->member->full_name`; `$roster->official` ada → `$roster->official->display_name` (atau fallback `external_name`); keduanya null → placeholder (mis. '—').
- **GIVEN** event dengan roster ber-`official_id` (member_id null),
  **WHEN** halaman Warta di-render,
  **THEN** tidak terjadi error "Attempt to read property 'full_name' on null".

### AC-T4-02 · Referensi `minister_name` dihapus
- **GIVEN** `grep -rn "minister_name" resources/views app database/factories`,
  **WHEN** dijalankan,
  **THEN** tidak ada hasil di blade (`warta-jemaat.blade.php`), factory (`MemberSacramentFactory`), atau query — kolom sudah di-drop di migrasi `2026_03_08_000003`; satu-satunya kemunculan yang boleh tersisa hanyalah di method `down()` migrasi tersebut.
- **GIVEN** blok "Pendeta:" di Section Sakramen blade Warta,
  **WHEN** dibaca,
  **THEN** menggunakan `$sacrament->official->display_name` (atau dihapus) — bukan `$sacrament->minister_name`.

### AC-T4-03 · Guard `Carbon::parse(null)` pada filter ulang tahun
- **GIVEN** `WartaJemaat::getReportData()` bagian birthdays,
  **WHEN** reviewer membaca query-nya,
  **THEN** ada guard sebelum `Carbon::parse($member->birth_date)` — query memakai `->whereNotNull('birth_date')` dan/atau `if ($member->birth_date)` — sehingga member tanpa tanggal lahir tidak masuk daftar dan `Carbon::parse(null)` tidak pernah dieksekusi.

### AC-T4-04 · Guard tanggal lahir di blade
- **GIVEN** `warta-jemaat.blade.php` pada kartu ulang tahun,
  **WHEN** reviewer membaca render tanggal (`Carbon::parse($member->birth_date)->format('d F')`),
  **THEN** dibungkus `@if ($member->birth_date)` (atau aksesor null-safe) sehingga member tanpa `birth_date` tidak dirender dengan tanggal nonsense.

### AC-T4-05 · Accept utama Warta tidak crash
- **GIVEN** kombinasi data: (a) roster `member_id` saja, (b) roster `official_id` saja (member null), (c) member tanpa `birth_date` dalam periode, (d) sakramen dengan `official_id`, (e) tidak ada event dalam periode,
  **WHEN** `getReportData()` dipanggil dan blade di-render,
  **THEN** halaman tampil normal tanpa exception (diverifikasi dari kode: semua akses properti relasi null-safe + query ter-guard).

---

## 8) T5 — Konsistensi Enum Keuangan `in/out` vs `debit/credit`

### AC-T5-01 · Satu sumber kebenaran tipe
- **GIVEN** seluruh aplikasi (form, tabel, seeder, laporan, widget),
  **WHEN** reviewer melakukan `grep -rn "'in'\|'out'" app/ database/`,
  **THEN** tidak ada penggunaan `'in'`/`'out'` sebagai nilai tipe transaksi **atau** tipe kategori keuangan — nilai kanonik tunggal: `debit` (Pemasukan) & `credit` (Pengeluaran).
- **GIVEN** `FinancialCategoryResource::form()` (Select `type`),
  **WHEN** dibaca,
  **THEN** options menggunakan `['debit' => 'Pemasukan', 'credit' => 'Pengeluaran']` (bukan `'in'/'out'`) — konsisten dengan `TransactionResource`, seeder, widget, dan laporan.

### AC-T5-02 · Kategori UI muncul di form transaksi
- **GIVEN** admin membuat `FinancialCategory` tipe `debit` (atau `credit`) melalui UI,
  **WHEN** membuka form `TransactionResource` dan memilih type `debit` (atau `credit`),
  **THEN** kategori tersebut muncul di dropdown `category_id` — filter `where('type', $get('type'))` kini cocok dengan nilai tersimpan (`debit`/`credit`), bukan `'in'/'out'`.
- **GIVEN** kategori seeder (`DefaultFinanceSeeder`),
  **WHEN** dibaca,
  **THEN** nilainya `'debit'`/`'credit'` (regression: kategori default tetap muncul di form).

### AC-T5-03 · Alur end-to-end kategori → transaksi → laporan
- **GIVEN** kategori `X` tipe `debit`,
  **WHEN** transaksi `type=debit` dengan `category_id=X` disimpan,
  **THEN** transaksi tampil di grup **Pemasukan** pada `WartaJemaat` dan masuk perhitungan `debit` di `LaporanRapatPage`; widget `StatsOverview`/`CashFlowChart` juga mengelompokkan `debit`/`credit` dengan benar — diverifikasi dari kode: semua query laporan memakai `debit`/`credit`.

### AC-T5-04 · Badge/label konsisten di semua tampilan
- **GIVEN** kolom `type` di tabel `TransactionResource` dan `FinancialCategoryResource`,
  **WHEN** reviewer membaca `formatStateUsing`,
  **THEN** keduanya memetakan `debit → 'Pemasukan'`, `credit → 'Pengeluaran'` (tidak ada mapping `'in'/'out'` tersisa).

---

## 9) T6 — Fix API Filament v5 (BadgeColumn)

### AC-T6-01 · `BadgeColumn` tidak dipakai lagi
- **GIVEN** `grep -rn "BadgeColumn" app/`,
  **WHEN** dijalankan,
  **THEN** hasil kosong — 4 resource (TransactionResource, UserResource, OfficialResource, FinancialCategoryResource) sudah tidak meng-import/menggunakan `Filament\Tables\Columns\BadgeColumn` (API lama, dihapus di Filament v5).

### AC-T6-02 · Tidak ada `->colors([...])` pada kolom
- **GIVEN** `grep -rn "->colors(" app/Filament/`,
  **WHEN** dijalankan,
  **THEN** hasil kosong di resource/table (API lama `colors()` diganti `->color(...)`/`->color(fn)` per Filament v5); yang tersisa hanya `Color::Amber` di `AdminPanelProvider` (bukan kolom).

### AC-T6-03 · Pola pengganti valid per resource
- **GIVEN** `TransactionResource` (badge type Pemasukan/Pengeluaran), `UserResource` (badge role), `OfficialResource` (badge tipe pelayan), `FinancialCategoryResource` (badge type),
  **WHEN** reviewer membaca masing-masing kolom,
  **THEN** pola yang dipakai API v5 yang valid: `TextColumn::make('x')->badge()->color(...)->formatStateUsing(...)` (atau `Column`/`IconColumn` setara) — tanpa `BadgeColumn`/`->colors()`/`->badge(fn)` lama.

### AC-T6-04 · Import tidak stale
- **GIVEN** bagian `use` tiap resource di atas,
  **WHEN** dibaca,
  **THEN** tidak ada `use Filament\Tables\Columns\BadgeColumn;` tersisa, dan tidak ada import kolom lama yang tidak terpakai.

### AC-T6-05 · Accept utama T6
- **GIVEN** keempat resource dimuat Filament v5,
  **WHEN** reviewer membaca seluruh rantai kolom (badge + color + formatStateUsing),
  **THEN** tidak ada pemanggilan method yang tidak dikenal pada kelas kolom v5 (tidak memicu "Call to undefined method" / "Class not found") — resource render tanpa error.

---

## 10) Checklist Verifikasi Cepat T2–T6 (baca kode)

| # | Pemeriksaan | Ekspektasi |
|---|---|---|
| 1 | `ls app/Policies/` | Ada policy (min. User, Official, Church, Transaction, Event) |
| 2 | `grep -rn "BadgeColumn\|->colors(" app/Filament/` | Kosong (T6) |
| 3 | `grep -rn "'in'\|'out'" app/ database/` | Kosong untuk tipe keuangan (T5) |
| 4 | `grep -rn "minister_name" resources/ app/ database/factories/` | Hanya di `down()` migrasi (T4) |
| 5 | Warta blade: `$roster->member->full_name` tanpa guard | Tidak ada (T4) |
| 6 | Warta blade: `Carbon::parse($member->birth_date)` tanpa `@if` | Tidak ada (T4) |
| 7 | UserResource field `password` pada operasi edit | `->dehydrated(false)` (T3) |
| 8 | UserResource create untuk church_admin | `church_id` fallback ke gereja sendiri (T3) |
| 9 | SystemCluster / UserResource / OfficialResource / ChurchResource | Ada pembatasan `super_admin` (T2) |
| 10 | `TransactionResource` category select vs `FinancialCategoryResource` type options | Nilai sama `debit`/`credit` (T5) |
