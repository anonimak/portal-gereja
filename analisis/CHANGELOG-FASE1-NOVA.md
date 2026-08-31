# CHANGELOG FASE 1 — Implementasi Langsung (Nova, atas temuan QA gate)

> Konteks: Pekerjaan Byte & Pixel awalnya dilaporkan selesai namun TIDAK tertulis ke repo
> bersama (mereka bekerja di sandbox dengan path berbeda, `app-filament/` vs `app/`).
> Nova mengambil alih implementasi langsung di repo bersama agar perubahan benar-benar ada.
> Verifikasi: statis (baca kode + grep). PHP/Composer tidak tersedia di sandbox.

## T1 — Isolasi church_id (Critical K1)
- **Baru** `database/migrations/2026_03_09_000001_add_church_id_to_member_sacraments_and_event_rosters.php`
  - Tambah `church_id` (nullable → FK churches → nullOnDelete) di `member_sacraments` & `event_rosters`.
  - Backfill aman: `member_sacraments.church_id` ← `members.church_id` (JOIN member_id);
    `event_rosters.church_id` ← `events.church_id` (JOIN event_id). Baris orphan dibiarkan NULL (unknown).
  - Index `church_id` di kedua tabel. Tidak ada data dihapus/dipindah antar gereja.
- `app/Models/MemberSacrament.php` → pakai `BelongsToChurch` (global scope + auto-fill creating).
- `app/Models/EventRoster.php` → pakai `BelongsToChurch`.
- `app/Filament/Widgets/StatsOverview.php` → semua query di-scope `church_id` (non-super_admin).
- `app/Filament/Widgets/CashFlowChart.php` → scope `church_id`.
- `app/Filament/Clusters/Reporting/Pages/WartaJemaat.php` → 4 query (Event, Member, Transaction, MemberSacrament)
  di-scope `church_id` + eager-load `official` + `whereNotNull('birth_date')` (guard Carbon::parse(null)).
- `SacramentsRelationManager.php` → `modifyQueryUsing` filter `church_id`.
- `database/factories/MemberSacramentFactory.php` → tambah `church_id` (dari member), hapus `minister_name`, `official_id => null`.
- `database/factories/EventRosterFactory.php` → tambah `church_id` (dari event), `official_id => null`.

## T2 — RBAC + Policy (Critical C6)
- **Baru** 14 policy di `app/Policies/`: TenantPolicy (base: view/update/delete hanya jika
  `record->church_id === user->church_id`, super_admin bebas), Church/User/Official (super_admin only),
  dan 10 policy tenant (extend TenantPolicy).
- `app/Providers/AppServiceProvider.php` → register `Gate::policy` untuk 13 model + `User::observe(UserObserver::class)`.
- `app/Filament/Clusters/System/SystemCluster.php` → `canAccess()` super_admin only (menu System disembunyikan).
- `UserResource` → `canViewAny/Create/Edit/Delete/DeleteAny` super_admin only; `Rule::in` whitelist role;
  `Rule::exists` church_id; password `dehydrated(filled)` (fix bug password null saat edit).

## T3 — Fix Privilege Escalation (Critical C5)
- **Baru** `app/Observers/UserObserver.php` — guard level model (3 lapis: form → mutation halaman → observer):
  non-super_admin dilarang create/edit/delete super_admin; dilarang pindah gereja; paksa church_id ke gereja
  aktor; super_admin tidak bisa menurunkan role sendiri / hapus akun sendiri; whitelist role.
- `CreateUser.php` → `mutateFormDataBeforeCreate` guard.
- `EditUser.php` → `mutateFormDataBeforeSave` guard + fix import `Filament\Actions\DeleteAction`.

## T4 — Fix Crash WartaJemaat (Critical C2)
- `warta-jemaat.blade.php` → roster null-safe: `$roster->member?->full_name ?? $roster->official?->display_name ?? 'Petugas'`;
  sakramen: `$sacrament->member?->full_name ?? 'Jemaat'`; ganti `minister_name` → `$sacrament->official?->display_name`;
  guard `@if ($member->birth_date)`.
- `WartaJemaat.php` → eager-load `official`, `whereNotNull('birth_date')`.
- `MemberSacramentFactory.php` → hapus `minister_name`.

## T5 — Enum keuangan in/out → debit/credit (Critical H1)
- `FinancialCategoryResource.php` → form & badge `debit/credit` (Pemasukan/Pengeluaran), BadgeColumn → TextColumn->badge().
- `FinancialCategoryFactory.php` → `debit/credit`.
- **Baru** `database/migrations/2026_03_09_000002_fix_financial_category_type_in_out_to_debit_credit.php`
  → UPDATE data lama 'in'→'debit', 'out'→'credit' (aman, tanpa hapus data).

## T6 — Fix Filament v5 BadgeColumn (Critical C3)
- `TransactionResource.php`, `UserResource.php`, `OfficialResource.php`, `FinancialCategoryResource.php`
  → `BadgeColumn` (API lama/removed) diganti `TextColumn::make(...)->badge()->color(...)`.

## Verifikasi (grep, di repo bersama)
- `grep BadgeColumn app/` → 0 match ✅
- `grep minister_name app/ resources/ database/factories/` → 0 match ✅
- `grep '=> 'in'' di FinancialCategory` → 0 match ✅
- `app/Policies/` → 14 file ✅ ; `app/Observers/UserObserver.php` ada ✅ ; 2 migrasi 2026_03_09 ada ✅

## Yang masih perlu verifikasi runtime (saat vendor/DB tersedia)
- `php artisan migrate` (backfill church_id + data fix kategori) di MySQL/SQLite.
- `php -l` / `vendor/bin/pint --dirty` — PHP CLI tidak tersedia di sandbox.
- Render list 4 resource + halaman Warta (roster official, member tanpa birth_date).
- Test RBAC: church_admin dilarang akses /admin/system/*.
