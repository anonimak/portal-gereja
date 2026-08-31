# SPEC-FASE2-T1 — Soft Delete + Audit Trail (Proteksi Data Historis)

- **Penulis**: Ada (Business Analyst)
- **Status**: DRAFT — menunggu review Nova & Vera
- **Repo**: portal-gereja (base: `origin/master` = `ee12e0d`)
- **Task**: Fase 2 — Task 1
- **Latar**: backlog B1–B2 `REVIEW-FASE1.md` — model `Member`, `Transaction`, `Event`, dan data sensitif lain saat ini **hard-delete** (termasuk `DeleteAction`/`DeleteBulkAction` di resource Filament). Ini berisiko menghilangkan data keuangan/historis gereja secara permanen, diperparah FK `cascadeOnDelete` (hapus `Fund`/`FinancialCategory` → transaksi ikut hilang).

---

## 0. Catatan Status Implementasi (penting untuk reviewer)

Saat dokumen ini ditulis, **implementasi awal sudah ada di local `master` (`785e9b9`, suite 57/57)** oleh Byte, namun **belum ter-push ke `origin/master`**:

| Item | Status di `785e9b9` |
|---|---|
| Trait `SoftDeletes` di 11 model (Member, Transaction, Event, Family, Fund, FinancialCategory, EventCategory, MinistryRole, MemberSacrament, EventRoster, Official) | ✅ Ada |
| Trait `AuditsActivity` (12 model, termasuk User) | ✅ Ada |
| Migrasi `audit_logs` + `deleted_at` | ✅ Ada |
| Test `SoftDeleteAuditTest` | ✅ Ada |
| Kolom `church_id` di `audit_logs` | ❌ **Belum** (gap) |
| Resource Filament `AuditLogResource` | ❌ **Belum** (gap) |
| `RestoreAction` / `ForceDeleteAction` di resource | ❌ **Belum** (gap) |
| `TrashedFilter` dipakai | ❌ Baru di-import, belum dipakai |
| FK `transactions.fund_id`/`category_id` → `restrictOnDelete` | ❌ Masih `cascadeOnDelete` |

Spec ini menetapkan **kebutuhan final** + acceptance criteria. Gap yang belum ada di atas menjadi **pekerjaan sisa Task 1** (lihat §7).

---

## 1. Model yang Wajib Soft Delete (rekomendasi + justifikasi)

| Model | Justifikasi |
|---|---|
| `Member` | Data jemaat historis; hard-delete sekarang menghapus sakramen via cascade. |
| `Transaction` | Data keuangan; prinsip akuntansi — transaksi tidak boleh hilang permanen. |
| `Event` | Riwayat ibadah/pelayanan; roster anak terkait. |
| `MemberSacrament` | Anak `Member`; ikut soft delete + restore agar riwayat spiritual utuh. |
| `EventRoster` | Anak `Event`; ikut soft delete + restore agar jadwal pelayan utuh. |
| `Family`, `Fund`, `FinancialCategory`, `EventCategory`, `MinistryRole`, `Official` | Data referensi/pelayanan yang masih bisa direferensikan data historis; soft delete mencegah kehilangan riwayat saat referensi dihapus. |

**Tidak di-soft-delete**: `Church`, `User` (identitas & akses), `AuditLog` (append-only).

**Perilaku relasi anak** (ditangani manual di observer/trait, bukan DB cascade):
- `Member → MemberSacrament`: saat member di-soft-delete → anak ikut `delete()`; saat `restore()` → anak ikut restore.
- `Event → EventRoster`: sama.
- DB `cascadeOnDelete` **tidak terpicu** oleh soft delete (hanya `UPDATE deleted_at`), jadi cascade manual di atas aman.

**Rekomendasi tambahan (penting)**: ubah FK `transactions.fund_id` dan `transactions.category_id` dari `cascadeOnDelete()` menjadi `restrictOnDelete()` — agar menghapus Fund/Kategori yang sudah dipakai transaksi **ditolak**, bukan menghapus transaksi. (Di luar logic keuangan; hanya perilaku referensial.)

---

## 2. Perilaku Relasi & Query Existing

### 2.1 Scope default (otomatis)
- Trait `SoftDeletes` menambah global scope `SoftDeletingScope` → semua query normal **otomatis exclude** baris `deleted_at IS NOT NULL`.
- Dampak positif ke kode existing:
  - `StatsOverview`, `CashFlowChart`, `WartaJemaat::getReportData()`, `LaporanRapatPage` → **tanpa perubahan kode** sudah tidak menyertakan data terhapus (karena memakai query Eloquent biasa + global scope `BelongsToChurch`).

### 2.2 Relasi yang butuh `withTrashed()`
- Tidak ada relasi yang **wajib** `withTrashed()` untuk kebutuhan fungsional normal.
- **Kebutuhan khusus** yang perlu dipertimbangkan saat implementasi:
  - Restore anak (sacrament/roster) harus memakai `withTrashed()` agar bisa menemukan parent yang sedang di-restore.
  - `assertChurchForeignKeysConsistent()` di `BelongsToChurch` sudah memakai `withoutGlobalScopes()` → **aman** terhadap SoftDeletes; FK tetap tervalidasi walau parent sedang soft-deleted.
- **Catatan**: jangan menambah `withTrashed()` secara membabi buta; hanya pada titik yang benar-benar perlu (restore/cascade).

### 2.3 Interaksi dengan global scope church
- Query existing tidak menulis `where('church_id', ...)` eksplisit di widget/laporan (mengandalkan global scope `BelongsToChurch`). SoftDeletes berjalan **paralel** tanpa konflik.

---

## 3. Audit Trail

### 3.1 Tabel baru `audit_logs` (skema minimal)
Skema saat ini (sudah ada di `785e9b9`) + **penambahan yang diminta**:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK users nullable, `nullOnDelete` | siapa yang mengubah; `null` untuk console/seeder |
| `church_id` | FK churches nullable, index | ⚠️ **GAP — WAJIB ditambah** untuk isolasi audit per gereja (tenant) |
| `action` | string | `create` / `update` / `delete` / `restore` / `forceDelete` |
| `auditable_type` | string | nama class model |
| `auditable_id` | unsignedBigInteger | ID record |
| `old_values` | json nullable | snapshot sebelum (untuk update/delete) |
| `new_values` | json nullable | snapshot sesudah (untuk create/update/restore) |
| `ip` | string(45) nullable | alamat IP pelaku |
| `created_at` | timestamp | append-only; **tidak ada `updated_at`** (`const UPDATED_AT = null`) |

Index: `['auditable_type', 'auditable_id']`, `user_id`, `church_id`.

### 3.2 Mekanisme pencatatan (trait)
- Trait `AuditsActivity` dipasang di model target (paralel dengan pola `BelongsToChurch`).
- Hook event: `created` → `create`; `updated` (skip jika hanya `deleted_at` berubah) → `update`; `deleted` → `delete`; `restored` → `restore`; `forceDeleted` → `forceDelete`.
- Capture:
  - `user_id` dari `auth()->id()` (nullable).
  - `church_id` dari record (atribut `$model->church_id`) — **GAP**: implementasi saat ini belum menulis `church_id`; wajib ditambah di `recordAudit()`.
  - `old_values`/`new_values`: array atribut; **password TIDAK pernah dicatat** (User dipasang trait tapi field password di-hide).
- Tidak ada rekursi: `AuditLog` sendiri tidak memakai trait ini.

### 3.3 Resource Filament untuk melihat audit log
- **Belum ada** → buat `AuditLogResource` (read-only: list + detail, tanpa create/edit/delete).
- **Kebijakan akses** (rekomendasi):
  - `super_admin` → melihat audit **semua gereja**.
  - `church_admin` → hanya audit **gereja sendiri** (pakai global scope `BelongsToChurch` pada model `AuditLog` + filter `church_id`).
  - `finance_admin` → **tidak** diizinkan (audit bersifat administratif).
  - Implementasi: policy `AuditLogPolicy` atau override `canViewAny`/`canView`; pastikan global scope church aktif di model `AuditLog`.

---

## 4. Soft Delete UI (resource Filament)

| Aksi | Perilaku target | Kebijakan |
|---|---|---|
| `DeleteAction` / `DeleteBulkAction` (sudah ada) | Menjadi **soft delete** otomatis karena model memakai `SoftDeletes` | sesuai policy resource (super_admin/church_admin/finance_admin utk keuangan) |
| `TrashedFilter` | **Aktifkan** di tabel resource (sudah di-import di `MembersTable`, belum dipakai) | semua role yang boleh akses resource |
| `RestoreAction` | **Belum ada** → tambahkan di recordActions | `church_admin` + `super_admin` (record gereja sendiri utk church_admin) |
| `ForceDeleteAction` | **Belum ada** → tambahkan (opsional, hati-hati) | **`super_admin` only** (kebijakan restore/hapus permanen) |
| `AuditLogResource` | read-only | lihat §3.3 |

Catatan: `TenantPolicy` sudah punya method `restore()` dan `forceDelete()` → tinggal dipakai oleh action Filament.

---

## 5. Batasan Scope & Asumsi Eksplisit

### Batasan
- **JANGAN menyentuh logic keuangan lain** (perhitungan saldo, laporan rapat, kalkulasi Warta). Task ini hanya: tambah kolom `deleted_at`, tambah tabel `audit_logs`, pasang trait, dan UI delete/restore.
- Tidak mengubah skema `transactions` selain (opsional) FK `restrictOnDelete` di §1 — di luar itu, kolom/laporan keuangan tidak disentuh.

### Asumsi
1. `WartaJemaat` **bukan model** (halaman dinamis) → tidak di-soft-delete; akurasi warta otomatis terjaga dari model penyusunnya.
2. `Church` & `User` tidak di-soft-delete.
3. Restore anak (sacrament/roster) dilakukan otomatis saat parent di-restore.
4. `finance_admin` tetap boleh soft-delete transaksi (sesuai policy Transaction yang mengizinkannya); **force delete** hanya super_admin.
5. Audit log **tidak bisa diedit/dihapus** dari UI.
6. Console/seeder tanpa aktor → `user_id` dan `church_id` bisa `null` (record tetap tercatat).
7. `audit_logs.church_id` diturunkan dari record (bukan aktor) agar audit super_admin yang mengubah data gereja lain tetap tercatat di gereja yang benar.

---

## 6. Acceptance Criteria (testable)

### A. Soft delete behavior
- **AC-SD-01** GIVEN member aktif WHEN `delete()` THEN record tidak muncul di query default (`Member::count()` turun) DAN `Member::withTrashed()->find(id)` masih ada, `deleted_at` terisi.
- **AC-SD-02** GIVEN member ter-soft-delete WHEN `restore()` THEN muncul kembali di query default dan `deleted_at` null.
- **AC-SD-03** GIVEN event ter-soft-delete WHEN dicek roster anaknya THEN `EventRoster::withTrashed()` masih ada (tidak ikut hilang permanen).
- **AC-SD-04** GIVEN member dengan sakramen WHEN member di-soft-delete THEN `MemberSacrament::withTrashed()` masih ada; setelah `restore()` sakramen ikut aktif.
- **AC-SD-05** GIVEN transaksi WHEN `forceDelete()` THEN record hilang permanen (tidak ada di `withTrashed()`).

### B. Audit tercatat
- **AC-AU-01** GIVEN admin membuat member WHEN create THEN baris `audit_logs` dengan `action='create'`, `user_id` = admin, `auditable_type` = `Member`, `new_values` berisi data.
- **AC-AU-02** GIVEN transaksi di-update WHEN update THEN baris `action='update'` dengan `old_values` & `new_values` yang benar.
- **AC-AU-03** GIVEN member di-soft-delete THEN baris `action='delete'` tercatat; setelah `restore()` baris `action='restore'` tercatat.
- **AC-AU-04** GIVEN seeder/factory tanpa login THEN baris audit tetap tercatat dengan `user_id=null`.
- **AC-AU-05** GIVEN update user (password) THEN field `password` TIDAK muncul di `old_values`/`new_values` audit.

### C. Tenant isolation audit per gereja
- **AC-TN-01** GIVEN gereja A dan B, masing-masing punya audit WHEN church_admin A query `AuditLog` THEN hanya baris `church_id` A yang tampil.
- **AC-TN-02** GIVEN super_admin mengubah member gereja B WHEN audit dicatat THEN `audit_logs.church_id` = gereja B (bukan gereja aktor).
- **AC-TN-03** GIVEN `AuditLogResource` WHEN church_admin buka halaman THEN hanya audit gereja sendiri; super_admin melihat semua.

### D. UI
- **AC-UI-01** GIVEN resource Member/Event/Transaction WHEN tabel dirender THEN `TrashedFilter` aktif dan `RestoreAction` tersedia untuk record terhapus.
- **AC-UI-02** GIVEN church_admin mencoba `ForceDeleteAction` THEN aksi ditolak (403/sembunyi); hanya super_admin yang bisa.

---

## 7. Pekerjaan Sisa (dari status `785e9b9` → final)

1. Tambah `church_id` di migrasi `audit_logs` (+ index) & isi di `recordAudit()`.
2. Pasang `BelongsToChurch` (atau scope manual) pada model `AuditLog` agar tenant isolation audit jalan.
3. Buat `AuditLogResource` read-only + kebijakan akses (§3.3).
4. Aktifkan `TrashedFilter` + tambah `RestoreAction` (+ `ForceDeleteAction` super_admin only) di resource terkait.
5. (Opsional) migrasi FK `transactions.fund_id`/`category_id` → `restrictOnDelete`.
6. Tambah test untuk AC-SD-04 (restore anak), AC-AU-05 (password), AC-TN-01/02/03 (tenant audit).

---

## 8. Checklist Verifikasi (baca kode / jalankan test)
- [ ] `grep -rn "SoftDeletes" app/Models` → 11 model tenant.
- [ ] `grep -rn "church_id" database/migrations/*audit_logs*` → kolom church_id ada + index.
- [ ] `grep -rn "RestoreAction" app/Filament` → ada di resource Member/Event/Transaction.
- [ ] `grep -rn "TrashedFilter" app/Filament` → dipakai (bukan hanya import).
- [ ] `app/Filament/Clusters/System/Resources/AuditLog/` → resource ada, read-only.
- [ ] `php vendor/bin/phpunit --no-coverage` → seluruh suite hijau (target ≥ 57 + test baru).
