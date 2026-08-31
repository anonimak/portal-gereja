# SPESIFIKASI TASK 3 — FASE 2: RBAC Granular + Fix Backlog MED Select + LOW-4

**Repo:** `portal-gereja` (Laravel 12 + Filament 5 + Livewire 4, multi-tenant `BelongsToChurch`)
**Penulis:** Ada (Business Analyst)
**Status:** SPEC — siap implementasi oleh Byte
**Base:** `origin/master` = `ff7efc5` (Fase 2 Task 2 kehadiran sudah merge, PR #5)
**Dasar:** backlog `REVIEW-FASE1.md` (MED form select), backlog Fase 2 LOW-4, kebutuhan owner RBAC per modul

---

## 0. Ringkasan

Task ini menutup 3 hal:

1. **RBAC granular lanjutan** — menambah mekanisme role/permission per modul agar client bisa
   membatasi akses (contoh: hanya kelola Jemaat, hanya Warta, hanya lihat laporan tanpa edit).
   Diputuskan **tanpa paket Spatie** — memakai permission keys internal yang konsisten dengan
   `TenantPolicy` yang sudah ada.
2. **Backlog MED (Fase 1)** — semua form select di resource masih memfilter
   `where('church_id', auth()->user()->church_id)` → `super_admin` tidak bisa memilih data gereja lain.
   Di-fix dengan helper `ChurchScope` + aturan aman lintas gereja.
3. **LOW-4 (Fase 2)** — soft-delete Member yang sedang menjabat `Official` (majelis lokal) tidak
   menonaktifkan jabatan tersebut → data usang tetap tampil "Aktif". Di-fix dengan observer + status
   Aktif/Nonaktif + FK `restrictOnDelete`.

Semua keputusan akses **di-server (Policy/Gate)**, bukan sekadar menyembunyikan menu.

---

## 1. Konteks & fakta kode saat ini (diverifikasi dari `origin/master`)

- Role berupa **string** di kolom `users.role`: `super_admin`, `church_admin`, `finance_admin`
  (tidak ada enum/helper).
- Policy sudah ada 15 file: `TenantPolicy` (base) + policy per model. `TenantPolicy` memakai
  `protected array $allowedRoles` (default `['super_admin','church_admin']`); resource keuangan
  (Transaction, Fund, FinancialCategory) override `allowedRoles` menambah `finance_admin`.
- Resource System (User/Official/Church) memakai `static canViewAny()` → `super_admin` only;
  `SystemCluster::canAccess()` → `super_admin` only.
- Cluster lain (Demographics, Events, MasterData, Reporting, Finance) **belum** punya `canAccess`.
- Halaman: `WartaJemaat::canAccess` = `super_admin`+`church_admin`; `LaporanRapatPage::canAccess` =
  3 role. Keduanya masih hardcode string role.
- **Semua** method policy `view/create/update/delete` memakai `hasModuleAccess()` yang sama →
  belum ada pemisahan izin **baca vs tulis** (semua role yang lolos modul otomatis bisa edit/hapus).
- Backlog MED: `grep "where('church_id', auth()->user()->church_id)" app/Filament` → **9 titik** nyata
  (lihat §5.2). `App\Support\ChurchScope` **belum ada**.
- LOW-4: `officials.member_id` masih `cascadeOnDelete` (migrasi `2026_03_08_000001`); `Official` tidak
  memakai `SoftDeletes` (kolom `deleted_at` sudah di-drop migrasi `000009`); `display_name` fallback ke
  `external_name ?? 'Unknown'` → member trashed tampil "Unknown" + `end_date` null → "Aktif".

---

## 2. Keputusan arsitektur RBAC: **internal, TANPA Spatie**

| Pertimbangan | Spatie | Internal (rekomendasi) |
|---|---|---|
| Pola existing | `TenantPolicy` + string role sudah ada — Spatie jadi lapisan paralel | Perluas pola yang sudah ada |
| Dependency/tabel | +1 package, +4 tabel pivot (`roles`, `permissions`, 2 pivot), migrasi & seed | 0 dependency, 1 enum + 1 registry |
| Query | Pivot lookup per gate | Array lookup di `RoleRegistry` (di-memory) |
| Tes | Perlu adaptasi `Gate::before` | Langsung via `Gate::forUser(...)->allows()` |
| Resource terbatas / tanpa command runtime | Tambah beban maintenance | Ringan, mudah diverifikasi baca kode |

**Keputusan: internal.** Alasan: pola Fase 1 sudah berbasis string role + Policy; kebutuhan hanya
6 role statis × 9 modul — tidak perlu engine permission dinamis. Menghindari dependency baru.

---

## 3. Model role/izin (internal)

### 3.1 Permission keys (satu sumber kebenaran, server-side)

Format: `<modul>.<kapabilitas>` dengan kapabilitas `view` / `create` / `update` / `delete`
(untuk attendance cukup `view` + `manage`).

| Modul | Permission keys | Resource yang memakainya |
|---|---|---|
| `member` | `member.view/create/update/delete` | Family, Member, MemberSacrament (Demographics) |
| `event` | `event.view/create/update/delete` | Event, EventRoster |
| `attendance` | `attendance.view` , `attendance.manage` | EventAttendance |
| `finance` | `finance.view/create/update/delete` | Transaction |
| `master.finance` | `master.finance.view/create/update/delete` | Fund, FinancialCategory |
| `master.event` | `master.event.view/create/update/delete` | EventCategory, MinistryRole |
| `report.warta` | `report.warta.view` | Halaman WartaJemaat |
| `report.rapat` | `report.rapat.view` | Halaman LaporanRapatPage |
| `system` | `system.view/create/update/delete` | Church, User, Official |

### 3.2 Komponen baru

1. **`app/Enums/UserRole.php`** (backed enum string) — 6 role:
   `SuperAdmin`, `ChurchAdmin`, `FinanceAdmin`, `JemaatAdmin`, `WartaEditor`, `ReportViewer`.
2. **`app/Enums/Permission.php`** (backed enum string) — daftar permission key di atas.
3. **`app/Support/RoleRegistry.php`** — `permissionsFor(UserRole): array<Permission>` +
   `has(User, Permission): bool` + `isCrossChurch(User): bool` (true hanya `super_admin`).
4. **`User::hasPermission(string $permission): bool`** — delegasi ke `RoleRegistry`.

### 3.3 Definisi role → permission (RoleRegistry)

| Role | Modul yang boleh (view + tulis kecuali disebut) |
|---|---|
| `super_admin` | **Semua** modul + lintas gereja (`isCrossChurch=true`) |
| `church_admin` | member, event, attendance, finance, master.finance, master.event, report.warta, report.rapat (tanpa `system`) |
| `finance_admin` | finance, master.finance (view+tulis), report.rapat.view (read-only) |
| `jemaat_admin` (BARU) | **hanya** member (view+tulis) |
| `warta_editor` (BARU) | report.warta.view + **view-only** member/event/attendance/finance/master.event/master.finance |
| `report_viewer` (BARU) | report.rapat.view + report.warta.view + **view-only** member/event/finance/master.finance |

Aturan tulis: modul yang diberi `create/update/delete` hanya untuk `super_admin`, `church_admin`,
dan (untuk modul keuangan) `finance_admin`. Role `warta_editor`/`report_viewer` **tidak pernah** dapat
kapabilitas tulis.

### 3.4 Integrasi titik existing (wajib sinkron)

- `User::canAccessPanel()` → izinkan 6 role panel (tambah 3 role baru).
- `UserObserver::ALLOWED_ROLES` → 6 role.
- `UserResource::form()` options role → 6 role (visible super_admin).
- Hapus/migrasikan `TenantPolicy::$allowedRoles` → diganti permission (lihat 3.5).

### 3.5 Upgrade `TenantPolicy` (capability-level)

```php
// Ganti protected array $allowedRoles dengan dua konstanta modul:
protected static string $module = 'member';          // subclass set per resource
// viewAny/view  → user->hasPermission("{$module}.view")
// create/update/delete/restore/forceDelete/deleteAny
//              → user->hasPermission("{$module}.create|update|delete") sesuai aksi
```

- `canAccessChurch()` (scope gereja) **tetap** — tidak berubah.
- Subclass resource: `EventPolicy::$module='event'`, `MemberPolicy::$module='member'`,
  `FamilyPolicy::$module='member'`, `MemberSacramentPolicy::$module='member'`,
  `TransactionPolicy::$module='finance'`, `FundPolicy`/`FinancialCategoryPolicy::$module='master.finance'`,
  `EventCategoryPolicy`/`MinistryRolePolicy::$module='master.event'`,
  `EventRosterPolicy::$module='event'`, `EventAttendancePolicy::$module='attendance'`.
- User/Official/Church policy: tetap standalone `super_admin` only (setara `system.*`).

### 3.6 Gating cluster & halaman (defense in depth, bukan sekadar hidden)

- `DemographicsCluster::canAccess()` → `member.view`
- `EventsCluster::canAccess()` → `event.view`
- `FinanceCluster::canAccess()` → `finance.view`
- `MasterDataCluster::canAccess()` → `master.event.view || master.finance.view`
- `ReportingCluster::canAccess()` → `report.warta.view || report.rapat.view`
- `SystemCluster::canAccess()` → `system.view` (tetap super_admin only)
- `WartaJemaat::canAccess()` → `report.warta.view`
- `LaporanRapatPage::canAccess()` → `report.rapat.view`

---

## 4. Matriks akses role × modul

Legend: ✅ = view+tulis; 👁 = view only; ❌ = ditolak

| Modul | super_admin | church_admin | finance_admin | jemaat_admin | warta_editor | report_viewer |
|---|---|---|---|---|---|---|
| Jemaat (member/family/sakramen) | ✅ | ✅ | ❌ | ✅ | 👁 | 👁 |
| Acara (event/roster) | ✅ | ✅ | ❌ | ❌ | 👁 | 👁 |
| Kehadiran (attendance) | ✅ | ✅ | ❌ | ❌ | 👁 | ❌ |
| Keuangan (transaction) | ✅ | ✅ | ✅ | ❌ | 👁 | 👁 |
| MasterData keuangan (fund/fincat) | ✅ | ✅ | ✅ | ❌ | 👁 | 👁 |
| MasterData acara (eventcat/ministryrole) | ✅ | ✅ | ❌ | ❌ | 👁 | ❌ |
| Warta Jemaat | ✅ | ✅ | ❌ | ❌ | 👁 | 👁 |
| Laporan Rapat & Keuangan | ✅ | ✅ | ✅ | ❌ | ❌ | 👁 |
| System (church/user/official) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Lintas gereja (view/select) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

Catatan: `warta_editor` perlu akses view data pendukung (member/event/keuangan) karena Warta
merender data tersebut; `report_viewer` butuh view event+finance untuk merender Laporan Rapat.

---

## 5. Backlog MED — form select lintas gereja untuk super_admin

### 5.1 Helper `app/Support/ChurchScope.php`

```php
final class ChurchScope
{
    /** Select pada form resource TOP-LEVEL: super_admin melihat semua gereja,
     *  non-super_admin hanya gereja sendiri. */
    public static function forActorSelect(Builder $query): Builder
    {
        $user = auth()->user();
        if ($user && $user->role !== 'super_admin') {
            $query->where('church_id', $user->church_id);
        }
        return $query;
    }

    /** Select pada form yang diturunkan dari induk (roster/sakramen/attendance):
     *  selalu ikut gereja OWNER RECORD, bukan gereja aktor dan bukan semua gereja. */
    public static function forChurch(int $churchId, Builder $query): Builder
    {
        return $query->where('church_id', $churchId);
    }
}
```

### 5.2 Titik perubahan (dari `grep` nyata — 9 titik + 1 tambahan)

**Pakai `forActorSelect` (form resource top-level):**
1. `MemberForm.php:30` — select `family_id`
2. `EventResource.php:63` — select `category_id`
3. `TransactionResource.php:55` — select `fund_id`
4. `TransactionResource.php:74` — select `category_id`
5. `TransactionResource.php:135` — `SelectFilter::make('fund_id')`
6. `OfficialResource.php:92` — select `member_id`

**Pakai `forChurch($ownerChurchId)` (form parent-derived):**
7. `EventResource.php:143` — roster select `member_id` (pertahankan `withTrashed()`, scope = gereja event)
8. `EventResource.php:156` — roster select `official_id` (scope = gereja event)
9. `EventResource.php:167` — roster select `role_id` (scope = gereja event)
10. *(Tambahan konsistensi)* `SacramentsRelationManager` select `official_id` — tambah scope
    `forChurch($ownerMemberChurch)` (sekarang tanpa filter → super_admin bisa memilih official gereja
    lain lalu kena 403 di guard; lebih baik opsi langsung dibatasi ke gereja member).

**Tidak diubah (sudah benar / bukan select opsi):**
- `AttendancesRelationManager` member select — sudah memakai `ownerChurchId()` (gereja event). Pertahankan.
- `WartaJemaat`/`LaporanRapatPage`/`StatsOverview`/`CashFlowChart` — komentar saja, bukan filter; jangan ditambah.
- `UserResource.php:144` — query tabel user super_admin (bukan form select opsi data tenant).

### 5.3 Aturan aman lintas gereja (batas — TIDAK boleh memindahkan data antar gereja)

1. **Helper hanya memengaruhi OPSI select**, tidak menonaktifkan global scope `BelongsToChurch`.
   Query resource/relasi tetap ter-scope: non-super hanya gereja sendiri; super melihat semua.
2. **Integritas FK lintas gereja tetap 403** — `assertChurchForeignKeysConsistent()` di
   `BelongsToChurch::saving` tetap aktif: super_admin yang memilih `category/fund/role/family/member/
   official` milik gereja berbeda dari record → 403.
3. **Field `church_id` pada form resource top-level** (Event, Transaction, Official, Member) —
   tampil **hanya untuk super_admin**, dengan `Rule::exists('churches','id')`. Tanpa ini, super_admin
   yang memilih opsi lintas gereja tetap menulis record ke gereja aktor (fallback `BelongsToChurch`),
   UX tidak sesuai harapan. Untuk resource via RelationManager (sakramen/roster/attendance),
   `church_id` sudah di-derive dari owner record — tidak perlu field tambahan.
4. Ringkas: **super_admin boleh MELIHAT & MEMILIH data semua gereja, tapi record baru selalu terikat
   satu gereja yang sah, dan tidak boleh mengaitkan data dari gereja berbeda.**

---

## 6. LOW-4 — Official saat Member soft-deleted

**Masalah:** `Official` tipe `majelis_lokal` menunjuk `member_id`; saat Member di-soft-delete, official
tetap `end_date=null` → tampil "Aktif", `display_name` jatuh ke "Unknown".

**Fix:**
1. **Observer `Member`** — pada `deleted` (non-force): untuk semua `Official` dengan
   `member_id = member->id` dan `end_date IS NULL` → set `end_date = now()->toDateString()`.
2. **Accessor `Official::isActive`** — `end_date === null || end_date >= today`, DAN (jika
   `type=majelis_lokal`) member tidak trashed. Dipakai untuk badge Aktif/Nonaktif di `OfficialResource`
   (ganti `formatStateUsing` "Aktif" naif).
3. **`Official::display_name`** — saat `member_id` terisi tapi member trashed, tampilkan
   `"{$member->full_name} (Nonaktif)"` (query `withTrashed()`), bukan "Unknown".
4. **Migrasi** — ubah `officials.member_id` dari `cascadeOnDelete` → `restrictOnDelete` (konsisten
   dengan `2026_03_09_000008`): `forceDelete` Member TIDAK menghapus data jabatan historis.
5. **Select member `OfficialResource`** — tetap tanpa `withTrashed()` (member trashed tidak bisa
   dipilih sebagai majelis baru). Sudah benar, jangan regresi.

Catatan asumsi: restore Member **tidak otomatis** mengembalikan `end_date` official (lihat §9.8).

---

## 7. Acceptance Criteria (GIVEN/WHEN/THEN — verifikasi baca kode)

### A. RBAC granular
- **AC-T3-01** GIVEN `app/Enums/UserRole.php` + `app/Support/RoleRegistry.php` ada, WHEN di-cek,
  THEN 6 role terdefinisi; `User::canAccessPanel` & `UserObserver::ALLOWED_ROLES` menerima 6 role;
  dan `grep -rn "'role' =>" app/Filament/Clusters/System/Resources/User` menampilkan 6 opsi.
- **AC-T3-02** GIVEN `TenantPolicy`, WHEN di-cek, THEN method view memakai `{$module}.view` dan method
  tulis (create/update/delete/restore/forceDelete/deleteAny) memakai `{$module}.create|update|delete`;
  `$allowedRoles` tidak lagi menjadi satu-satunya gerbang.
- **AC-T3-03 (finance_admin)** WHEN `Gate::forUser(finance_admin)`, THEN `denies('viewAny', Member/Event/
  EventCategory/MinistryRole/MemberSacrament/EventAttendance)` dan `allows('viewAny', Transaction/Fund/
  FinancialCategory)` serta `WartaJemaat::canAccess()=false`, `LaporanRapatPage::canAccess()=true`.
- **AC-T3-04 (jemaat_admin)** WHEN role `jemaat_admin`, THEN `allows('viewAny', Member/Family/
  MemberSacrament)`, dan `denies('viewAny', Event/Transaction/...)` + `denies('create', Member)` **tidak**
  (jemaat_admin boleh tulis member) — verifikasi `allows('create', Member)`.
- **AC-T3-05 (warta_editor)** THEN `allows('viewAny', Member/Event/Transaction/...)`, `denies('create'/
  'update'/'delete', Member)` & `denies('create', Event)`, `WartaJemaat::canAccess()=true`,
  `LaporanRapatPage::canAccess()=false`.
- **AC-T3-06 (report_viewer)** THEN `allows('viewAny', Transaction/Fund/Event/Member)`,
  `LaporanRapatPage::canAccess()=true`, `denies('create', Transaction)` & `denies('update', Fund)`,
  `denies('viewAny', EventAttendance)`.
- **AC-T3-07 (regresi)** THEN `church_admin` & `super_admin` & `finance_admin` matriks akses persis
  seperti Fase 2 saat ini (suite existing `RbacPageAccessTest` tetap hijau).
- **AC-T3-08** GIVEN cluster Demographics/Events/Finance/MasterData/Reporting, WHEN di-cek, THEN
  masing-masing punya `canAccess()` berbasis permission (bukan `allowedRoles` string).
- **AC-T3-09 (server-side)** GIVEN user role yang ditolak, WHEN membuka URL langsung resource/page
  (mis. `/admin/...`), THEN mendapat 403/404 — bukan sekadar nav tersembunyi (di-cover test).

### B. Select lintas gereja
- **AC-T3-10** GIVEN `app/Support/ChurchScope.php`, THEN ada `forActorSelect()` & `forChurch()`;
  `grep -rn "where('church_id', auth()->user()->church_id)" app/Filament` = **kosong**.
- **AC-T3-11 (super_admin)** WHEN membuka form Event/Transaction/Official/Member, THEN opsi select
  (category/fund/member/family/dst) berasal dari **semua gereja**.
- **AC-T3-12 (non-super)** WHEN `church_admin`/`finance_admin` membuka form yang sama, THEN opsi tetap
  hanya gereja sendiri (helper memfilter).
- **AC-T3-13 (parent-derived)** WHEN super_admin membuka event/member gereja B lalu mengisi roster/
  sakramen, THEN opsi member/official/role di-scope ke gereja **B** (owner record), bukan gereja aktor.
- **AC-T3-14 (church_id field)** GIVEN form top-level tenant, THEN ada field `church_id` `visible`
  hanya super_admin dengan `Rule::exists('churches','id')`; super_admin create event untuk gereja B
  → `church_id` record = B; memilih `category_id` gereja A → 403 (guard FK).
- **AC-T3-15 (regresi laporan)** THEN `WartaJemaat`/`LaporanRapatPage`/widgets tetap tanpa filter
  eksplisit church_id (global scope penjaga tenant) — tidak ada perubahan query di file tsb.

### C. LOW-4
- **AC-T3-16** GIVEN Member `majelis_lokal` dengan `end_date=null`, WHEN Member di-soft-delete, THEN
  `end_date` official = tanggal soft delete; `Official::isActive=false`.
- **AC-T3-17** WHEN Member tersebut di-restore, THEN `end_date` official **tetap** terisi (tidak
  otomatis dikembalikan — admin set manual); `isActive` mengikuti `end_date`.
- **AC-T3-18** GIVEN `OfficialResource` tabel, THEN badge menampilkan Aktif/Nonaktif dari `isActive`;
  `display_name` member trashed tampil "(Nonaktif)" bukan "Unknown".
- **AC-T3-19** GIVEN migrasi baru, THEN `officials.member_id` = `restrictOnDelete`; `forceDelete`
  Member tidak menghapus baris `officials` (cek FK di migrasi + test).
- **AC-T3-20** THEN select member `OfficialResource` tetap mengecualikan member trashed.

---

## 8. Daftar test wajib (unit/feature — nama file contoh)

1. `tests/Feature/RbacGranularMatrixTest.php` — matriks Gate per role × modul (AC-T3-03..07).
2. `tests/Feature/RbacPageAccessTest.php` (extend) — URL langsung role ditolak → 403 (AC-T3-09);
   tambah role baru di setUp.
3. `tests/Feature/ChurchScopeSelectTest.php` — helper `forActorSelect`/`forChurch` (AC-T3-10..13).
4. `tests/Feature/CrossChurchCreateTest.php` — super_admin create event/transaction untuk gereja B,
   pilih FK lintas gereja → 403; church_id field (AC-T3-14).
5. `tests/Feature/OfficialAutoDeactivateTest.php` — soft delete member → official end_date;
   restore → end_date tetap; forceDelete → official tidak hilang (AC-T3-16..19).
6. Regresi: seluruh suite existing (SoftDeleteAudit, TenantIsolation, BelongsToChurchGuard,
   RbacPageAccess, UserEscalation, ReReviewFixes, WartaJemaat, EventAttendance) tetap hijau.

---

## 9. Asumsi eksplisit

1. **Tidak memakai Spatie** — internal permission keys + RoleRegistry (konsisten `TenantPolicy`,
   tanpa dependency & tabel pivot).
2. Role bertambah **3 → 6** (`jemaat_admin`, `warta_editor`, `report_viewer`). Perilaku 3 role lama
   tidak berubah (regresi dilarang).
3. `jemaat_admin` = CRUD modul Jemaat saja (Family/Member/Sakramen), tanpa modul lain.
4. `warta_editor` = lihat Warta + data pendukung **read-only**; tanpa kapabilitas tulis apa pun.
5. `report_viewer` = lihat Laporan Rapat & Keuangan + Warta **read-only**; tanpa tulis.
6. Override permission per-user (kolom `permissions` JSON) **DITUNDA** — cukup role-based untuk task ini.
7. Field `church_id` pada form top-level tenant hanya untuk super_admin; non-super tidak berubah.
8. Restore Member tidak otomatis mengembalikan `end_date` official (administratif manual).
9. Select parent-derived (roster/sakramen/attendance) memakai `forChurch(ownerChurchId)` — bukan
   actor scope maupun semua gereja — untuk menjaga integritas FK saat super_admin.
10. Tidak menyentuh query Warta/Laporan/Stats (global scope tetap penjaga tenant).

---

## 10. Checklist perubahan file (untuk Byte)

**Baru:** `app/Enums/UserRole.php`, `app/Enums/Permission.php`, `app/Support/RoleRegistry.php`,
`app/Support/ChurchScope.php`, migrasi `officials.member_id` → restrict, test (5 file baru).

**Ubah:** `app/Policies/TenantPolicy.php` (+ 8 policy subclass set `$module`),
`app/Filament/Clusters/{Demographics,Events,Finance,MasterData,Reporting}/*Cluster.php` (canAccess),
`app/Filament/Clusters/Reporting/Pages/WartaJemaat.php` + `app/Filament/Pages/LaporanRapatPage.php`
(canAccess permission), `app/Models/User.php` (canAccessPanel + hasPermission),
`app/Models/Official.php` (isActive, display_name), `app/Observers/UserObserver.php`,
`app/Observers/MemberObserver.php` (atau booted Member), `app/Filament/Clusters/System/Resources/User/
UserResource.php` (6 role), `EventResource.php`, `TransactionResource.php`, `OfficialResource.php`,
`MemberForm.php`, `SacramentsRelationManager.php`, `OfficialResource.php` (badge).

**Tidak diubah:** Warta/Laporan/Stats query, `AttendancesRelationManager` member select,
`UserResource.php:144`, `SystemCluster`, `UserObserver` guard eskalasi (Fase 1).
