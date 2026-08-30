# Backend Review — portal-gereja (Laravel + Filament)

- **Reviewer**: Byte (Backend Developer)
- **Cakupan**: Models, Trait `BelongsToChurch`, `ChurchObserver`, Migrations, Filament Resources (Members, Transaction, User, Family, Event, Official, Church, MasterData), Pages (WartaJemaat, LaporanRapat), Widgets, Providers, Routes, Seeders.
- **Stack terdeteksi**: Laravel 12, `filament/filament ^5.0` (kode memakai API `Filament\Schemas\Schema` — bukan API Filament v3 murni).
- **Catatan penting**: Tidak ada folder `app/Policies`; tidak ada tes keamanan/multi-tenancy (hanya `ExampleTest`).

**Ringkasan severity**: Critical 3 · High 7 · Medium 7 · Low 6

---

## CRITICAL

### C1. Tidak ada kontrol akses berbasis role sama sekali (kecuali ChurchResource)
- Semua cluster/resource terbuka untuk semua user login (`church_admin`, `finance_admin`), termasuk **System → UserResource** dan **OfficialResource**.
- `AdminPanelProvider` tidak punya `->canAccess`, tidak ada `canViewAny()` pada resource lain, tidak ada Policy, cluster tidak meng-override `canAccess()`.
- **Dampak**: `finance_admin`/`church_admin` bisa kelola user, lihat/ubah data gereja lain (lihat C2/C3), kelola data master.
- **Rekomendasi**: buat Policy per resource (`UserPolicy`, `OfficialPolicy`, dll), batasi akses cluster per role (mis. hanya `super_admin`/`church_admin` untuk System), daftarkan di `AuthServiceProvider`, atau override `canAccess()` di tiap cluster/page.

### C2. Privilege escalation via UserResource (role & church_id tanpa validasi server)
- `Select::make('role')` hanya membatasi pilihan di UI (`options`). **Tidak ada rule server-side** (`->in()` / `Rule::in`), dan `role` + `church_id` ada di `$fillable` `User`.
- `UserResource::table()` memang memfilter daftar via `modifyQueryUsing`, tetapi **User tidak punya global scope** → halaman Edit/Delete (`/{record}`) me-resolve record berdasarkan ID tanpa cek kepemilikan gereja → **IDOR antar gereja**.
- **Dampak**: `church_admin` bisa mengirim request crafted (`role=super_admin`, `church_id=<gereja lain>`, ganti password/email) untuk membuat/mengubah user — termasuk dirinya sendiri — menjadi **Super Admin**, atau mengedit user gereja lain.
- **Rekomendasi**: tambahkan validasi `->in(['church_admin','finance_admin'])` untuk non-super-admin; sembunyikan **dan** `->dehydrated(false)` `role`/`church_id` bagi non-super-admin; jangan izinkan edit user gereja lain (scope query + policy `update/delete`); jangan izinkan edit user sendiri/menghapus diri; gunakan enum + cast untuk `role`.

### C3. Kebocoran data lintas gereja di halaman WartaJemaat (MemberSacrament tanpa tenant scope)
- `MemberSacrament` **tidak punya kolom `church_id`** dan **tidak memakai trait `BelongsToChurch`**, tetapi `WartaJemaat::getReportData()` menjalankan:
  `MemberSacrament::with('member')->whereBetween('sacrament_date', ...)->get()` → mengambil **data sakramen semua gereja**.
- View lalu memanggil `$sacrament->member->full_name`; untuk member gereja lain, relasi `member` tersaring global scope → `null` → **crash ErrorException** (selain berisiko eksposur data lintas tenant di memori).
- **Rekomendasi**: tambahkan `church_id` + trait `BelongsToChurch` pada `member_sacraments` (dan `event_rosters`), atau paksa query selalu lewat parent (`Member`/`Event`) yang sudah ter-scope + filter `church_id` eksplisit. Tambahkan migrasi backfill.

---

## HIGH

### H1. EventRoster & MemberSacrament: FK lintas gereja tanpa penegakan integritas tenant
- Kedua tabel tidak punya `church_id`. FK `member_id`, `official_id`, `role_id` (roster) dan `official_id` (sacrament) hanya di-filter di UI, **tidak ada validasi server** bahwa record terkait berasal dari gereja yang sama dengan parent (`event`/`member`).
- **Dampak**: request crafted bisa menautkan petugas/sakramen gereja A ke member/official gereja B → inkonsistensi data + potensi kebocoran nama via relasi.
- **Rekomendasi**: tambahkan `church_id` + trait pada kedua model; tambahkan rule validasi "FK harus satu gereja" di form; pertimbangkan composite FK `(church_id, id)`.

### H2. WartaJemaat crash pada roster berisi official
- View memanggil `$roster->member->full_name` tanpa cek null; roster tipe official (`member_id = null`, `official_id` terisi) → `null->full_name` → error.
- Juga eager load hanya `member`,`role`, tidak `official`.
- **Rekomendasi**: gunakan `optional($roster->member)->full_name ?? $roster->official?->display_name`, eager-load `official.member`.

### H3. Inkonsistensi enum FinancialCategory: `in/out` vs `debit/credit`
- `FinancialCategoryResource` memakai `type = 'in'/'out'`, sedangkan seeder & `TransactionResource` memakai `'debit'/'credit'` (filter `where('type', $get('type'))`).
- **Dampak**: kategori yang dibuat lewat UI **tidak muncul** di dropdown transaksi; badge/color `in/out` salah.
- **Rekomendasi**: seragamkan ke `debit`/`credit` (termasuk migrasi data), atau jadikan satu konstanta enum.

### H4. N+1 & query boros
- `WartaJemaat`: semua member aktif di-load lalu di-filter di PHP (`->get()->filter(...)`); transaksi di view mengakses `$transaction->fund->name` & `->category->name` tanpa eager load → N+1 per baris.
- `MembersTable`: `family.name` tanpa eager load (N+1).
- `OfficialResource`: kolom `display_name` memanggil `$this->member` per baris (N+1); `searchable(['external_name','member.full_name',...])` pada accessor tidak efisien/rawan salah.
- **Rekomendasi**: `->with(['family'])`, `->with(['fund','category'])`, hitung ulang tahun via `whereMonth`/`whereDay`, eager-load `member` pada Official table, hindari searchable pada accessor.

### H5. Route export `/admin/laporan-rapat/export-excel` rapuh & tidak aman
- `app(LaporanRapatPage::class)->form->fill(...)` di luar siklus Livewire → `$page->form` kemungkinan `null` → fatal.
- Tidak ada validasi input (`period_type`, `month`, `quarter`, `year`); nilai invalid bisa membuat `Carbon::create` aneh.
- Middleware `['auth','verified']` padahal user seed `email_verified_at = null` → route praktis tidak bisa dipakai / redirect ke verifikasi yang tidak ada.
- **Rekomendasi**: pindahkan export menjadi **Action Filament** di dalam `LaporanRapatPage` (dengan validasi form + policy), atau controller tersendiri + `FormRequest` + cek role; hapus route duplikat ini.

### H6. Hard delete total tanpa soft deletes / audit
- Tidak ada `SoftDeletes` di model mana pun; `DeleteAction`/bulk delete menghapus permanen + cascade (member → sacraments/rosters; family → members; church → semua data).
- `TrashedFilter` di-import `MembersTable` tapi tidak dipakai.
- **Rekomendasi**: tambahkan `SoftDeletes` pada entitas penting (Member, Family, Event, Transaction, Official, Fund, Category), aktifkan `TrashedFilter`, atau minimal konfirmasi + audit log (siapa menghapus, kapan).

### H7. Unique validation global tidak scoped per gereja
- `MemberForm::id_card_number` dan `FamilyResource::family_number` memakai `->unique(ignoreRecord: true)` → validasi **global**, padahal DB hanya unique per `church_id` (`unique(['church_id','family_number'])`).
- **Dampak**: dua gereja tidak bisa memakai NIK/no. keluarga yang sama meski DB mengizinkan (mis. jemaat titipan lintas gereja).
- **Rekomendasi**: custom Rule dengan `where('church_id', auth()->user()->church_id)` (Rule::unique → `->where()`).

---

## MEDIUM

### M1. Trait BelongsToChurch bergantung pada `auth()` (fragile untuk job/console/tests)
- Global scope hanya aktif jika `auth()->check()`; tanpa auth (queue, seeder, tinker, test) scope **tidak aktif** → query mengembalikan **semua gereja**.
- `creating` hook juga hanya mengisi `church_id` saat ada auth; event/church_id nullable jadi invisible.
- **Rekomendasi**: gunakan tenant resolver eksplisit (mis. `currentChurchId()` dari session/context) dengan fallback aman (return kosong/abort) saat konteks tidak ada; jangan andalkan `auth()` di dalam scope.

### M2. `Event.church_id` nullable
- Event yang dibuat tanpa konteks auth (console/import) akan `null` dan tidak terlihat siapa pun (scope `where church_id = X`).
- **Rekomendasi**: jadikan `church_id` non-nullable (`foreignId()->constrained()->cascadeOnDelete()`), konsisten dengan tabel tenant lain.

### M3. Tidak ada proteksi login/password/verifikasi
- Password tanpa `->confirmed()`/min length; tidak ada rate limiting khusus login; user seed `email_verified_at = null` tapi panel tidak memakai `verified`.
- **Rekomendasi**: gunakan `->passwordRules()`, `->confirmed()`, konfigurasi rate limiter login, dan aktifkan verifikasi email jika perlu.

### M4. Role sebagai string bebas tanpa enum/cast
- `users.role` adalah string tanpa constraint/cast; nilai tak dikenal lolos dan tidak tertangani di banyak `match` (default gray).
- **Rekomendasi**: `enum` + `cast` + validasi `Rule::in`.

### M5. ChurchResource: bulk delete berpotensi bypass cek per-record
- `canViewAny/canCreate/canUpdate/canDelete` dioverride untuk super_admin, tapi `canDeleteAny`/`canBulkDelete` (dipakai `DeleteBulkAction`) tidak dioverride.
- **Rekomendasi**: override `canDeleteAny()` (dan `canDeleteAll()`) juga, atau hapus bulk delete.

### M6. WartaJemaat: binding `wire:model.live` pada property `?Carbon`
- `public ?Carbon $startDate` di-bind ke `<input type="date" wire:model.live>` — nilai string dari browser bisa gagal hydrasi/type juggling ke Carbon → error saat `$startDate->startOfDay()`.
- **Rekomendasi**: gunakan `DatePicker` Filament atau property string + parse manual, dan beri cast.

### M7. Hardening env/config
- `.env.example` memakai `APP_DEBUG=true`, `SESSION_ENCRYPT=false`; pastikan produksi `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` (HTTPS), `APP_KEY` ter-generate.
- **Rekomendasi**: audit `.env` produksi + dokumentasi deployment.

---

## LOW

### L1. Referensi kolom yang sudah di-drop
- View `warta-jemaat.blade.php` masih membaca `$sacrament->minister_name`, padahal kolom di-drop migrasi `2026_03_08_000003` → selalu null (dead code). Hapus.

### L2. Import/komentar tak terpakai
- `TrashedFilter` di `MembersTable`, beberapa `use BackedEnum`/`Heroicon` yang dikomentari; bersihkan.

### L3. Tidak ada tes keamanan/multi-tenancy
- Hanya `ExampleTest`; tidak ada feature test isolasi tenant, authorization, atau privilege escalation.
- **Rekomendasi**: tulis test: (1) user gereja A tidak bisa melihat/mengubah data gereja B, (2) non-super-admin tidak bisa membuat super admin, (3) scope trait aktif pada semua query tenant.

### L4. Ketidakcocokan versi Filament
- `composer.json` meminta `filament/filament ^5.0` dan kode memakai `Filament\Schemas\Schema` (API v4/v5); prompt menyebut Filament 3. Pastikan versi terpasang konsisten — jika benar v3, banyak kode tidak kompatibel.

### L5. LaporanRapatPage untuk super_admin
- `getReportData()` memakai `auth()->user()->church_id` eksplisit → super admin hanya melihat gereja miliknya, tidak semua gereja (inkonsisten dengan peran).
- **Rekomendasi**: untuk super_admin beri pilihan gereja atau tampilkan semua.

### L6. UX/edge kecil
- `EventResource` Select `official` memakai `relationship('official','id',...)` → search by id bukan display_name.
- `FundResource` memakai `BulkAction::make('delete')` custom tanpa `DeleteBulkAction` standar (minor).
- `DatabaseSeeder` memakai password `'password'` untuk semua user — hanya untuk dev, jangan dipakai produksi.

---

## Rekomendasi Arsitektur (ringkas)

1. **Tenant isolation terpusat**: semua tabel tenant wajib punya `church_id` (non-nullable) + trait `BelongsToChurch`; tambahkan ke `event_rosters` & `member_sacraments`. Ganti dependensi `auth()` di trait dengan resolver konteks eksplisit.
2. **Authorization**: implementasikan Policy per resource + batasi akses cluster per role; jangan pernah hanya mengandalkan sembunyian field UI.
3. **Validasi server-side**: setiap relasi lintas tabel divalidasi satu gereja; gunakan rule `in()`/`exists()` dengan scope church.
4. **SoftDeletes + audit** untuk mencegah kehilangan data permanen.
5. **Tes otomasi** untuk isolasi multi-tenant & RBAC sebagai gerbang CI.
