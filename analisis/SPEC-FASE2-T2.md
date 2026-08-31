# SPESIFIKASI FASE 2 — TASK 2: MODUL KEHADIRAN IBADAH PER ANGGOTA (CHECK-IN)

**Repo:** `portal-gereja` (Laravel 12 + Filament 5 + Livewire 4, multi-tenant `BelongsToChurch`)
**Penulis:** Ada (Business Analyst)
**Status:** DRAFT v1.1 — amend hasil review Vera (PR #4): strategi UNIQUE×SoftDeletes, klarifikasi AC-T2-10, server-side AC-T2-08. Siap review Nova → delegasi implementasi ke Byte (branch + PR)
**Dasar:** `REVIEW-FASE1.md` (backlog B: kehadiran masih manual L/P), hasil analisis Fase 1 (top fitur #2 kehadiran per anggota)

---

## 0. Konteks & Fakta Kode Saat Ini (diverifikasi dari master `ba00e8c`)

- `events` punya kolom agregat `attendance_male` / `attendance_female` (input manual di `EventResource` → Fieldset "Kehadiran").
- `Event::getTotalAttendanceAttribute()` = `attendance_male + attendance_female`; dipakai di tabel `EventResource`, CSV `LaporanRapatPage`, dan (via accessor) Warta.
- **Tidak ada pencatatan kehadiran per anggota.** Tidak ada tabel `event_attendances` / model `EventAttendance`.
- Pola tenant sudah matang: trait `BelongsToChurch` (global scope + `churchForeignKeyMap()` + `deriveChurchIdFromParent()`), pola anak `EventRoster` (punya `event_id`, `member_id`, `official_id`, `role_id`) adalah contoh terbaik yang harus ditiru.
- Fase 2 Task 1 sudah merged: semua model tenant memakai `SoftDeletes` + `RecordsAuditTrail` (audit `created/updated/deleted/restored/force_deleted`, `church_id` di audit_logs, redaksi password). **Model baru harus konsisten dengan pola ini.**
- RBAC sudah ada pola `TenantPolicy` (`$allowedRoles`); `EventRosterPolicy extends TenantPolicy` (default `super_admin` + `church_admin`). `User::canAccessPanel` membatasi 3 role: `super_admin`, `church_admin`, `finance_admin`.

---

## 1. Model & Skema

### 1.1 Keputusan: kehadiran **per acara (event)**, bukan entitas "ibadah reguler" terpisah

**Rekomendasi: `event_attendances` (per event).**

Alasan:
- Semua konsumen data (Warta, LaporanRapat, roster, kalender acara) sudah berbasis `Event`. Menambah entitas "ibadah mingguan" terpisah akan menduplikasi konsep dan memaksa migrasi besar.
- Ibadah mingguan reguler cukup direpresentasikan sebagai `Event` dengan kategori ibadah (mis. EventCategory "Ibadah Minggu"). **Jadwal berulang/recurring** adalah task terpisah (lihat Asumsi A1) — bukan blocker untuk kehadiran.
- Kolom "tanggal" tidak perlu disimpan terpisah: tanggal kehadiran **diturunkan dari `events.start_datetime`** (hindari redundansi & inkonsistensi tanggal).

### 1.2 Skema tabel `event_attendances`

| kolom | tipe | ket |
|---|---|---|
| `id` | bigint PK | |
| `church_id` | FK `churches` (cascadeOnDelete) + index | NOT NULL (tabel baru) |
| `event_id` | FK `events` (cascadeOnDelete) + index | |
| `member_id` | FK `members` (cascadeOnDelete) + index | |
| `status` | enum `hadir` / `tidak_hadir`, default `hadir` | **di-set server-side** (lihat AC-T2-08) |
| `checked_in_at` | timestamp, nullable | saat check-in; **di-set server-side** |
| `checked_in_by` | FK `users` (nullOnDelete), nullable | **audit-only**, tidak masuk FK map; **di-set server-side** |
| `notes` | text, nullable | keterangan opsional (sakit, izin, dll.) |
| `deleted_at` | timestamp, nullable | SoftDeletes (konsisten Task 1) |
| `created_at` / `updated_at` | timestamps | |
| **UNIQUE** | `(event_id, member_id)` | anti check-in ganda — **tanpa partial index** (lihat 1.4) |

**Migrasi:** `database/migrations/xxxx_create_event_attendances_table.php` (ikuti urutan migrasi existing, tanggal `2026_03_10_xxxxxx`).

### 1.3 Model `EventAttendance`

```php
class EventAttendance extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    protected function churchForeignKeyMap(): array
    {
        return ['event_id' => Event::class, 'member_id' => Member::class];
        // checked_in_by TIDAK dimasukkan — audit-only (super_admin boleh
        // check-in event gereja lain tanpa kena 403, konsisten pola roster).
    }

    protected function deriveChurchIdFromParent(): ?int
    {
        // church_id mengikuti gereja event (sama seperti EventRoster).
    }

    // fillable: church_id, event_id, member_id, status, checked_in_at,
    //           checked_in_by, notes
    // casts: checked_in_at => 'datetime'

    public function event(): BelongsTo { ... }     // Event
    public function member(): BelongsTo { ... }    // Member
    public function checkedInBy(): BelongsTo { ... } // User::class, 'checked_in_by'
}
```

### 1.4 Strategi UNIQUE(event_id, member_id) × SoftDeletes — **restore-or-create** (BLOCKING dari review Vera)

**Masalah:** `UNIQUE(event_id, member_id)` melarang dua record aktif untuk pasangan yang sama. Tapi karena `EventAttendance` memakai `SoftDeletes`, skenario ini muncul:
1. Admin check-in member X pada event E → record `(E, X)` aktif.
2. Admin menghapus check-in X (soft delete) → `deleted_at` terisi, **record tetap ada di DB**.
3. Admin check-in ulang X pada event E yang sama → `INSERT (E, X)` baru **melanggar UNIQUE** karena record lama (soft-deleted) masih menempati constraint.

**Opsi yang DITOLAK:** partial unique index `UNIQUE(event_id, member_id) WHERE deleted_at IS NULL` — tidak portabel ke MySQL (production), karena MySQL tidak mendukung partial/filtered index.

**Strategi yang dipilih — restore-or-create (wajib):**
- Saat check-in (satuan maupun massal) menerima `(event_id, member_id)`:
  - Cari record **termasuk trashed** (`withTrashed()->where([...])->first()`).
  - **Jika record soft-deleted ditemukan → RESTORE record lama** (bukan create baru):
    - `deleted_at` di-set NULL;
    - `status`, `checked_in_at = now()`, `checked_in_by = auth()->id()` diperbarui ke nilai check-in terbaru;
    - `updated_at` terisi normal.
    - Audit mencatat event **`restored`** (pola `RecordsAuditTrail` Task 1 sudah menangani event `restored` — strategi ini **tidak memecah audit trail**; justru mempertahankan jejak hidup record lama).
  - **Jika record aktif ditemukan (deleted_at NULL) → duplikat, dilewati** tanpa error dan tanpa mengubah record (konsisten perilaku massal saat ini).
  - **Jika tidak ada record → CREATE baru** (audit `created`).
- `forceDelete` lama lalu create baru **TIDAK dipakai** sebagai jalur utama karena memutus jejak audit (record lama hilang permanen, tercatat `force_deleted` + `created` baru alih-alih `restored`).
- Aturan ini berlaku di **service layer / helper check-in** (`EventAttendance::checkIn($event, $member, $actor)` atau sejenisnya), dipakai oleh form satuan dan aksi massal — bukan logika di masing-masing UI.

---

## 2. Relasi & Scope Tenant

- `Event::attendances()` → `hasMany(EventAttendance::class)`; `Event::totalAttendanceFromRecords()` atau ubah accessor `total_attendance` (lihat 3.4).
- `Member::attendances()` → `hasMany(EventAttendance::class)` (untuk rekap per member).
- **Scope default:** otomatis ter-scope `church_id` lewat global scope `BelongsToChurch` — non-`super_admin` hanya melihat gereja sendiri, `super_admin` melihat semua. **Tidak boleh** ada query manual `where('church_id', auth()->user()->church_id)` di kode baru (pakai global scope + helper select yang sudah ada).
- **FK silang:** `churchForeignKeyMap()` memastikan member/event pada record kehadiran **satu gereja** dengan record-nya (`assertChurchForeignKeysConsistent` → 403 bila beda gereja).
- Relasi `member()`/`event()` memakai relasi Eloquent standar; karena kedua induk juga SoftDeletes, gunakan `withTrashed()` hanya bila perlu menampilkan data historis (mis. rekap member yang sudah di-soft-delete) — di luar default.
- **Catatan khusus strategi 1.4:** pencarian record untuk restore-or-create **wajib memakai `withTrashed()`** agar record soft-deleted ketemu; scope `BelongsToChurch` tetap berlaku (pencarian tetap dibatasi `church_id` — tidak boleh lintas gereja).

---

## 3. Fitur UI

**Kesimpulan: cukup backend + Filament (resource / relation manager / page), TIDAK perlu frontend khusus.**

### 3.1 Input kehadiran per acara (admin)
- Tambah **`AttendancesRelationManager`** di `EventResource::getRelations()` → tab **"Kehadiran"**.
- Tabel: `member.full_name` (searchable), `status` (badge), `checked_in_at` (datetime), `checked_in_by.name`, `notes`.
- Actions: `CreateAction` (Select member — searchable, ter-scope gereja utk non-super; `status` default `hadir`; `checked_in_at` = `now()`, `checked_in_by` = `auth()->id()` **otomatis di server — field ini TIDAK ada di form**), `EditAction` (hanya `notes`/`status` — lihat AC-T2-08), `DeleteAction`, `RestoreAction` (soft delete).
- **Check-in massal:** header action "Check-in Massal" → multi-select daftar member → submit → panggil helper check-in untuk setiap member: member dengan record **aktif** dilewati, member dengan record **soft-deleted** di-restore (1.4), member baru di-create. Duplikat tidak pernah error.

### 3.2 Rekap kehadiran per member (riwayat)
- Relation manager **"Riwayat Kehadiran"** di `MemberResource` (atau satu `AttendancesRelationManager` yang dipakai di kedua resource dengan konfigurasi berbeda).
- Tabel: `event.title`, `event.start_datetime`, `status`, `checked_in_at`, `notes` — riwayat per anggota.

### 3.3 Laporan sederhana per periode
- Reuse pola `LaporanRapatPage`/`WartaJemaat` (filter bulan/triwulan + rentang tanggal): daftar event pada periode + total hadir per event + total keseluruhan, opsional pecahan L/P dari `member.gender`.
- Bentuk minimal: tampilan baru (Filament `Page`) + (opsional) tombol export CSV mengikuti pola `exportToExcel()` yang sudah ada. **Tidak perlu** chart/dashboard baru di task ini.

### 3.4 Integrasi dengan data lama & Warta
- Kolom `attendance_male`/`attendance_female` **tidak dihapus** (data historis) — disembunyikan/deprecate di form `EventResource`.
- Ubah accessor `Event::total_attendance` → **jika event TIDAK punya record attendance sama sekali** → fallback `attendance_male + attendance_female` (data lama); **jika ada ≥1 record** (berapapun statusnya) → `count()` record `status='hadir'` (tanpa fallback). Dengan ini tabel event, CSV LaporanRapat, dan Warta **otomatis** memakai angka baru tanpa perubahan tampilan.
- `WartaJemaat::getReportData()` cukup menambah eager load `attendances.member` pada query event (opsional, hanya jika mau pecahan L/P di Warta).

---

## 4. RBAC

Ikuti pola `TenantPolicy` yang sudah ada:

- Buat **`EventAttendancePolicy extends TenantPolicy`** — `$allowedRoles = ['super_admin', 'church_admin']` (default sudah benar, tidak perlu override).
- **`finance_admin`: TIDAK** diberi akses kehadiran (modul Events + Demographics di luar scope-nya; ia hanya Finance/MasterData keuangan/LaporanRapat).
- **`church_admin`:** input + rekap kehadiran untuk gereja sendiri (record ter-scope).
- **`super_admin`:** akses penuh lintas gereja; saat check-in event gereja lain, `church_id` record mengikuti gereja **event** (via `deriveChurchIdFromParent`).
- Gating UI: relation manager berada di dalam `EventResource` yang sudah di-gate; tambah policy model sebagai lapisan kedua (URL langsung → 403).

---

## 5. Acceptance Criteria (GIVEN / WHEN / THEN)

### Skema & model
- **AC-T2-01** GIVEN migrasi baru dijalankan, THEN tabel `event_attendances` ada dengan kolom: `church_id`, `event_id`, `member_id`, `status`, `checked_in_at`, `checked_in_by`, `notes`, `deleted_at`, timestamps, FK + index, dan **UNIQUE(event_id, member_id)** **tanpa partial index** (karena MySQL prod tidak mendukung partial index — lihat 1.4).
- **AC-T2-02** GIVEN model `EventAttendance`, THEN ia memakai `BelongsToChurch`, `RecordsAuditTrail`, `SoftDeletes`, `churchForeignKeyMap()` berisi `event_id` & `member_id`, dan `deriveChurchIdFromParent()` membaca `church_id` dari event.
- **AC-T2-03** GIVEN relasi model, THEN `Event::attendances()` dan `Member::attendances()` ada; `checked_in_by` TIDAK ada di `churchForeignKeyMap()`.

### Tenant isolation & integritas
- **AC-T2-04** GIVEN admin gereja A, WHEN membuat attendance dengan `member_id` milik gereja B pada event gereja A, THEN ditolak (403 / validasi gagal) — via `assertChurchForeignKeysConsistent`.
- **AC-T2-05** GIVEN `super_admin`, WHEN check-in event gereja B, THEN record `church_id` = gereja B (bukan gereja aktor).
- **AC-T2-06** GIVEN admin gereja A, WHEN query `EventAttendance` default, THEN hanya record `church_id` = A yang dikembalikan (global scope; tanpa `where('church_id', ...)` manual di kode baru).

### UI & perilaku
- **AC-T2-07** GIVEN halaman detail event, THEN ada tab/relation manager "Kehadiran" dengan create/edit/delete/restore.
- **AC-T2-08** GIVEN create/edit attendance via form, THEN `status`, `checked_in_at`, dan `checked_in_by` **TIDAK bisa di-set dari input form** — semuanya di-set server-side: `status` default `hadir` (perubahan hanya via aksi/controller yang divalidasi), `checked_in_at` = `now()` pada saat check-in, `checked_in_by` = `auth()->id()`. Form hanya menerima `member_id` (create) dan `notes` (create/edit); field status/checked_in_at/checked_in_by **tidak ada sebagai input** sehingga user tidak bisa mengisi status seenaknya.
- **AC-T2-09** GIVEN check-in massal, THEN hanya member yang belum punya record pada event tsb yang dibuat; **member dengan record soft-deleted di-restore (1.4)**; duplikat aktif dilewati tanpa error.
- **AC-T2-10** GIVEN event yang **TIDAK punya record attendance sama sekali** (tabel `event_attendances` kosong untuk event tsb), THEN `total_attendance` = fallback `attendance_male + attendance_female`. GIVEN event yang **punya ≥1 record attendance** (berapapun statusnya — termasuk semua `tidak_hadir`), THEN `total_attendance` = jumlah record `status='hadir'` dan **fallback legacy TIDAK dipakai**.
- **AC-T2-11** GIVEN field `attendance_male`/`attendance_female`, THEN tidak lagi tampil di form `EventResource` (hidden/deprecate) tapi kolom DB tetap ada.

### RBAC
- **AC-T2-12** GIVEN `finance_admin`, WHEN akses modul kehadiran / `EventAttendancePolicy`, THEN ditolak (false).
- **AC-T2-13** GIVEN `church_admin`, THEN dapat membuat/mengedit attendance gereja sendiri; URL langsung record gereja lain → 403.

### Audit & soft delete
- **AC-T2-14** GIVEN create/update/delete/restore attendance, THEN baris `audit_logs` tercatat (action `created`/`updated`/`deleted`/`restored`, `user_id`, `church_id`).
- **AC-T2-15** GIVEN attendance di-soft-delete, THEN tidak muncul di list default tapi data tetap ada di DB (`withTrashed()`).
- **AC-T2-16** GIVEN laporan periode (Warta/LaporanRapat), THEN total kehadiran per event diambil dari `attendances` (status hadir) dengan fallback legacy (hanya saat tidak ada record sama sekali), tanpa error.
- **AC-T2-17** *(baru — hasil review Vera, BLOCKING)* GIVEN member X punya record attendance **soft-deleted** untuk event E, WHEN check-in ulang X pada E (satuan atau massal), THEN **record lama di-restore** (`deleted_at` = NULL, `status`/`checked_in_at`/`checked_in_by` diperbarui), **tidak ada record baru dibuat**, UNIQUE tidak dilanggar, dan audit mencatat event `restored` (bukan `created` baru).
- **AC-T2-18** *(baru)* GIVEN member X punya record attendance **aktif** untuk event E, WHEN check-in ulang X pada E (satuan atau massal), THEN duplikat dilewati tanpa error — tidak ada record baru dan record lama tidak diubah.

### Test yang harus ditulis (feature tests, pola `tests/Feature/*Test.php` + RefreshDatabase)
1. `test_attendance_create_dan_relasi_terisi` (AC-08)
2. `test_attendance_duplicate_ditolak_unique` (AC-01, 09)
3. `test_attendance_cross_church_ditolak` (AC-04)
4. `test_attendance_super_admin_mengikuti_church_event` (AC-05)
5. `test_attendance_scope_tenant_terisolasi` (AC-06)
6. `test_attendance_total_fallback_legacy` (AC-10)
7. `test_attendance_soft_delete_dan_restore` (AC-15)
8. `test_attendance_audit_tercatat` (AC-14)
9. `test_attendance_finance_admin_ditolak` (AC-12)
10. `test_attendance_bulk_checkin_skip_duplicate` (AC-09)
11. `test_attendance_recheckin_setelah_soft_delete_melakukan_restore` (AC-17) — **wajib**: delete → re-check-in → record lama ter-restore, UNIQUE aman, audit `restored` tercatat
12. `test_attendance_recheckin_duplikat_aktif_dilewati` (AC-18)
13. `test_attendance_form_tidak_menerima_status_checked_in_dari_input` (AC-08 — verifikasi field tidak ada / diabaikan dari request)

---

## 6. Asumsi Eksplisit

- **A1.** Jadwal ibadah berulang / recurring event **di luar scope** task ini (task terpisah). Kehadiran tetap berbasis `Event`; ibadah reguler dibuat manual sebagai event berkategori "Ibadah".
- **A2.** Kehadiran hanya untuk **member terdaftar**. Tamu/kunjungan di luar scope; opsi `guest_count` manual ditunda (P2).
- **A3.** Kolom "tanggal" tidak dibuat terpisah — tanggal kehadiran = `events.start_datetime`.
- **A4.** `attendance_male`/`attendance_female` tetap di DB sebagai fallback & data historis; tidak dihapus.
- **A5.** Role tetap 3 (`super_admin`, `church_admin`, `finance_admin`); `finance_admin` **tidak** dapat akses kehadiran.
- **A6.** `checked_in_by` bersifat audit-only dan tidak divalidasi silang gereja.
- **A7.** Soft delete & audit trail pada `EventAttendance` mengikuti pola Task 1 (trait `SoftDeletes` + `RecordsAuditTrail`), termasuk pencatatan event `restored`.
- **A8.** Tidak ada perubahan pada logic keuangan / LaporanRapat di luar penyesuaian accessor `total_attendance`.
- **A9.** *(baru)* Restore-or-create (1.4) menjadi satu-satunya jalur check-in (satuan & massal) melalui helper/service bersama — bukan logika terpisah di tiap UI. `forceDelete`+create baru tidak dipakai sebagai jalur utama demi menjaga audit trail.
- **A10.** *(baru)* Pencarian record untuk restore-or-create memakai `withTrashed()` **tetap dalam scope `church_id`** — tidak pernah mencari lintas gereja.

---

## Lampiran — File yang Dibuat/Diubah

**Baru:**
- `database/migrations/xxxx_create_event_attendances_table.php`
- `app/Models/EventAttendance.php`
- `app/Services/EventAttendanceService.php` *(atau helper static di model — tempat logika restore-or-create 1.4)*
- `app/Policies/EventAttendancePolicy.php`
- `app/Filament/Clusters/Events/Resources/Event/RelationManagers/AttendancesRelationManager.php`
- (Opsional) halaman rekap kehadiran per periode + test `tests/Feature/EventAttendanceTest.php`

**Ubah:**
- `app/Models/Event.php` — relasi `attendances()`, accessor `total_attendance` (record → fallback legacy)
- `app/Models/Member.php` — relasi `attendances()`
- `app/Filament/Clusters/Events/Resources/Event/EventResource.php` — sembunyikan field L/P, daftarkan RelationManager
- `app/Filament/Clusters/Demographics/Resources/Members/MemberResource.php` — (opsional) relation manager "Riwayat Kehadiran"
- `app/Filament/Clusters/Reporting/Pages/WartaJemaat.php` — eager load `attendances.member` (opsional)
- `database/factories/EventAttendanceFactory.php` (+ seeder bila perlu)
