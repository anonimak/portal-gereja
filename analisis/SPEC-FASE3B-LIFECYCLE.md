# SPESIFIKASI FASE 3B — SIKLUS HIDUP GEREJA (LIFECYCLE): KELAHIRAN → BAPTIS → SIDI → NIKAH → KEMATIAN

**Repo:** `portal-gereja` (Laravel 12 + Filament 5 + Livewire 4, multi-tenant `BelongsToChurch`)
**Penulis:** Ada (Business Analyst)
**Status:** DRAFT — siap review Nova → delegasi implementasi Byte (branch + PR)
**Base:** `origin/master` = `0c22249` (diverifikasi dari kode, bukan asumsi)
**Dasar:** backlog Fase 1 (sakramen masih berupa record sederhana `member_sacraments` tanpa proses), kebutuhan owner "prosesi gereja yang bisa direkam + terbitkan dokumen"

---

## 0. Konteks & Fakta Kode Saat Ini (diverifikasi dari master `0c22249`)

- **`members`**: status enum `aktif | titipan | pindah | meninggal`; `family_relation` `kepala_keluarga | istri | anak | lainnya`; `birth_place`, `birth_date`, `gender`; `BelongsToChurch` + `SoftDeletes` + `RecordsAuditTrail`; `churchForeignKeyMap: ['family_id']`; booted soft-delete/restore sakramen anak.
- **`member_sacraments`**: type enum `penyerahan | baptis_anak | sidi | baptis_dewasa | nikah`; kolom `sacrament_date`, `official_id` (FK `officials`), `certificate_number`; `churchForeignKeyMap: ['member_id','official_id']`; `deriveChurchIdFromParent` dari `member`.
- **`officials`**: type `majelis_lokal | pendeta_internal | pelayan_tamu`; `member_id` nullable (majelis lokal), `external_name`/`origin_church` (pelayan tamu), `start_date`/`end_date`. **Bukan** SoftDeletes.
- **`events`**: `category_id`, `title`, `start_datetime`, `end_datetime`, `location`, `attendance_male/female`; `SoftDeletes`; booted soft-delete/restore roster anak.
- **`event_rosters`**: `event_id`, `member_id` (nullable), `official_id` (nullable), `role_id`; `churchForeignKeyMap` 4 FK.
- **Pola tenant** matang: trait `BelongsToChurch` (global scope + `churchForeignKeyMap()` + `deriveChurchIdFromParent()` + `assertChurchForeignKeysConsistent()` 403 lintas gereja). Semua model tenant memakai `SoftDeletes` + `RecordsAuditTrail` (audit `created/updated/deleted/restored/force_deleted` + `church_id` di audit_logs).
- **RBAC**: `TenantPolicy` ($allowedRoles) + policy per model; `User::canAccessPanel` membatasi 3 role. **Spec T3 (PR #7, sudah di master)** menetapkan upgrade ke 6 role + permission keys internal tanpa Spatie (`UserRole`, `Permission`, `RoleRegistry`, `User::hasPermission`) — implementasi Byte mungkin sedang berjalan. Fase 3B **menambah modul `lifecycle`** ke matriks T3.
- **PDF**: belum ada library PDF (tidak ada dompdf/snappy di composer). Halaman cetak existing (Warta, LaporanRapat) memakai **blade + print CSS** → pendekatan dokumen Fase 3B mengikuti pola yang sama.
- **WartaJemaat** membaca `member_sacraments` per minggu → record sakramen baru otomatis muncul di Warta (tanpa ubah Warta).

---

## 1. Keputusan Arsitektur

1. **Cluster baru `Lifecycle`** di Filament (sejajar Demographics/Events), berisi 4 resource: `BirthRecord`, `GuidanceProgram` (dengan relation manager sesi + peserta), `Marriage`, `Death`. Sakramen Baptis/Sidi tetap memakai tabel `member_sacraments` (konsisten dengan Warta & riwayat sakramen yang sudah ada).
2. **Tidak ada paket baru** untuk lifecycle: semua memakai trait existing (`BelongsToChurch`, `SoftDeletes`, `RecordsAuditTrail`) dan pola `TenantPolicy`. **PDF = blade + print CSS** (print-to-PDF browser), konsisten dengan Warta. Opsi dompdf dicatat sebagai tugas terpisah (LOW).
3. **Semua tabel baru wajib** punya `church_id` NOT NULL, `BelongsToChurch`, `churchForeignKeyMap`, `deriveChurchIdFromParent` (kecuali yang memang berdiri sendiri), `SoftDeletes`, `RecordsAuditTrail`. Konsisten penuh dengan Task 1/2.
4. **Unique × SoftDeletes** (pivot peserta sesi) memakai pola **restore-or-create** dari T2 (AC-T2-17/18) — bukan partial index (tidak portabel MySQL).
5. **Status anggota** hanya berubah otomatis untuk **kematian** (`member.status = 'meninggal'`). Baptis/Sidi/Nikah TIDAK mengubah `member.status` (itu milestone rohani, dicatat di `member_sacraments`), dan TIDAK mengubah `family_relation` secara otomatis (asumsi A6).

---

## 2. Model Data & Relasi

### 2.1 `birth_records` — Kelahiran (Akta Lahir)

| kolom | tipe | ket |
|---|---|---|
| `id` | bigint PK | |
| `church_id` | FK `churches` + index | NOT NULL |
| `member_id` | FK `members` + index | **UNIQUE** (1 member = 1 akta) |
| `birth_order` | tinyint, nullable | anak ke-berapa |
| `birth_place_full` | string, nullable | tempat lahir utk dokumen (boleh beda dari `members.birth_place`) |
| `birth_date` | date | tanggal lahir (salinan utk dokumen; sumber utama tetap `members.birth_date`) |
| `father_name` | string, nullable | nama ayah (dari kepala keluarga; diizinkan edit utk dokumen) |
| `mother_name` | string, nullable | nama ibu |
| `certificate_number` | string, nullable | no. akta |
| `issued_at` | date, nullable | tanggal terbit |
| `notes` | text, nullable | |
| `deleted_at` | timestamp nullable | |
| `created_at`/`updated_at` | timestamps | |

- `BelongsToChurch`; `churchForeignKeyMap: ['member_id' => Member::class]`; `deriveChurchIdFromParent` dari `member`.
- Relasi: `birthRecord.member()`, `member.birthRecord()` (hasOne).

### 2.2 `guidance_programs` — Bimbingan Pra-Sidi & Pra-Nikah (payung sesi)

| kolom | tipe | ket |
|---|---|---|
| `id` | bigint PK | |
| `church_id` | FK + index | NOT NULL |
| `type` | enum `pra_sidi \| pra_nikah` | |
| `title` | string | mis. "Katakisasi Angkatan 2026-1" |
| `start_date` / `end_date` | date, nullable | rentang program |
| `status` | enum `draft \| berjalan \| selesai \| batal`, default `draft` | `selesai` = syarat terbit dokumen |
| `notes` | text, nullable | |
| `deleted_at` | timestamp nullable | |
| timestamps | | |

- `BelongsToChurch` (tanpa FK silang → `churchForeignKeyMap` kosong; `church_id` dari aktor / form).
- Relasi: `guidanceProgram.sessions()` (hasMany), `guidanceProgram.members()` (hasManyThrough via sessions? — **jangan**, pakai `sessions.members` per sesi).

### 2.3 `guidance_sessions` — Satu pertemuan bimbingan

| kolom | tipe | ket |
|---|---|---|
| `id` | bigint PK | |
| `church_id` | FK + index | NOT NULL |
| `program_id` | FK `guidance_programs` + index | |
| `title` | string, nullable | topik pertemuan |
| `session_at` | datetime | **waktu pertemuan** (bisa dijadwalkan) |
| `location` | string, nullable | |
| `official_id` | FK `officials`, nullable + index | Pendeta/Majelis pembimbing |
| `notes` | text, nullable | |
| `deleted_at` | timestamp nullable | |
| timestamps | | |

- `BelongsToChurch`; `churchForeignKeyMap: ['program_id' => GuidanceProgram::class, 'official_id' => Official::class]`; `deriveChurchIdFromParent` dari `program`.

### 2.4 `guidance_session_members` — Peserta per pertemuan (pivot dengan kehadiran)

| kolom | tipe | ket |
|---|---|---|
| `id` | bigint PK | |
| `church_id` | FK + index | NOT NULL |
| `session_id` | FK `guidance_sessions` + index | |
| `member_id` | FK `members` + index | |
| `attended` | bool, default false | kehadiran sesi ini |
| `notes` | text, nullable | |
| `deleted_at` | timestamp nullable | |
| timestamps | | |
| **UNIQUE** | `(session_id, member_id)` | tanpa partial index |

- `BelongsToChurch`; `churchForeignKeyMap: ['session_id' => GuidanceSession::class, 'member_id' => Member::class]`; `deriveChurchIdFromParent` dari `session`.
- **Check-in/penambahan ulang setelah soft-delete → restore-or-create** (pola T2 §1.4): cari `withTrashed()->where([session_id, member_id])` → restore kalau ada record soft-deleted, lewati kalau aktif, create kalau belum ada. Pencarian tetap dalam scope `church_id`.
- Untuk **pra_nikah**: peserta = **2 member** (pasangan) — cukup tambah 2 baris pivot per sesi; tidak perlu kolom `role`.

### 2.5 `marriages` — Pernikahan (Akta Nikah)

| kolom | tipe | ket |
|---|---|---|
| `id` | bigint PK | |
| `church_id` | FK + index | NOT NULL |
| `husband_member_id` | FK `members` + index | |
| `wife_member_id` | FK `members` + index | |
| `marriage_date` | date | |
| `official_id` | FK `officials`, nullable + index | Pendeta pemberkatan |
| `location` | string, nullable | |
| `witness_names` | json, nullable | saksi |
| `program_id` | FK `guidance_programs`, nullable | pranikah yang diselesaikan |
| `certificate_number` | string, nullable | no. akta nikah |
| `issued_at` | date, nullable | |
| `notes` | text, nullable | |
| `deleted_at` | timestamp nullable | |
| timestamps | | |

- `BelongsToChurch`; `churchForeignKeyMap: ['husband_member_id' => Member::class, 'wife_member_id' => Member::class, 'official_id' => Official::class, 'program_id' => GuidanceProgram::class]`; `deriveChurchIdFromParent` dari `husband_member_id`.
- **Sinkron sakramen:** saat `marriages` dibuat, otomatis buat **2 baris `member_sacraments`** (type `nikah`, satu per pasangan) → otomatis muncul di Warta & riwayat sakramen masing-masing. Tambah kolom `member_sacraments.marriage_id` (nullable) sebagai penanda. Hapus marriage → soft-delete 2 baris sakramen itu.

### 2.6 `member_deaths` — Kematian

| kolom | tipe | ket |
|---|---|---|
| `id` | bigint PK | |
| `church_id` | FK + index | NOT NULL |
| `member_id` | FK `members` + index | **UNIQUE** |
| `death_date` | date | |
| `burial_date` | date, nullable | |
| `burial_location` | string, nullable | |
| `official_id` | FK `officials`, nullable | Pendeta yang melayani |
| `event_id` | FK `events`, nullable | event ibadah pemakaman (opsional) |
| `notes` | text, nullable | |
| `deleted_at` | timestamp nullable | |
| timestamps | | |

- `BelongsToChurch`; `churchForeignKeyMap: ['member_id' => Member::class, 'official_id' => Official::class, 'event_id' => Event::class]`; `deriveChurchIdFromParent` dari `member`.
- **Side effect:** saat `member_deaths` dibuat → `member.status = 'meninggal'`. Restore/hapus death **tidak** otomatis mengembalikan status (asumsi A8).

### 2.7 Modifikasi tabel existing

- `member_sacraments`: tambah `marriage_id` (FK `marriages`, nullable, index), `program_id` (FK `guidance_programs`, nullable, index), `issued_at` (date, nullable), `document_path` (string, nullable — cadangan untuk file PDF tersimpan). Type enum **tidak diubah** (`penyerahan | baptis_anak | sidi | baptis_dewasa | nikah` — sudah mencakup semua kebutuhan).
- `members`: **tidak** mengubah enum status. (Opsional helper `Member::markDeceased()`.)
- `guidance_programs`: type `pra_nikah` boleh juga dipakai pra-sidi — dua-duanya satu tabel, dibedakan `type`.

---

## 3. Alur Proses per Sakramen/Prosesi

### 3.1 Kelahiran
1. User (church_admin/super_admin) membuat/memilih **Member** (keluarga baru/ada; `family_relation='anak'`, status default `aktif`).
2. Buat **BirthRecord** (bisa via tombol "Catat Kelahiran" di MemberResource atau resource BirthRecord). `birth_date`/`birth_place_full` diambil dari member (boleh di-edit untuk dokumen). Nama orang tua diisi otomatis dari keluarga (kepala + istri) — bisa diedit.
3. Terbitkan **Akta Lahir** (PDF blade) — lihat §4.
4. Audit: `created` pada member + birth_record.

### 3.2 Baptis Anak
1. Member sudah ada (bayi/anak).
2. Buat record **`member_sacraments` type=`baptis_anak`** (via SacramentsRelationManager existing atau tombol khusus) — `sacrament_date`, `official_id` (Pendeta), `certificate_number`, `issued_at`.
3. Terbitkan **Dokumen Baptis Anak** (§4).
4. Record otomatis muncul di Warta minggu tsb (tanpa ubah Warta).

### 3.3 Bimbingan Pra-Sidi (katakisasi)
1. Buat **GuidanceProgram** `type=pra_sidi` → status `draft`.
2. Jadwalkan **GuidanceSession** (`session_at`, `official_id` = Pendeta/Majelis pembimbing) — beberapa sesi per program.
3. Tambah **peserta** (member) per sesi via pivot `guidance_session_members`; tandai `attended` tiap pertemuan.
4. Setelah semua sesi & peserta tuntas → status program = `selesai`.
5. **Terbitkan sakramen:** buat `member_sacraments` **`type=sidi`** (untuk yang sudah baptis anak) atau **`type=baptis_dewasa`** (yang belum pernah dibaptis) — pilihan di UI saat penerbitan; isi `program_id` = program pra-sidi.
6. Terbitkan **Dokumen Baptis/Sidi** (§4).

### 3.4 Baptis Dewasa (Sidi) — penerbitan
- Sama dengan 3.3 langkah 5–6; dokumen bernama "Dokumen Baptis" (untuk `baptis_dewasa`) / "Dokumen Sidi" (untuk `sidi`).

### 3.5 Bimbingan Pra-Nikah
1. Buat **GuidanceProgram** `type=pra_nikah`.
2. Jadwalkan **GuidanceSession** (sama seperti pra-sidi).
3. Tambah **2 peserta** (pasangan) per sesi; tandai kehadiran keduanya.
4. Status program = `selesai`.
5. Lanjut ke **Marriage** (3.6) dengan `program_id` diisi.

### 3.6 Pernikahan
1. Buat **Marriage**: `husband_member_id`, `wife_member_id`, `marriage_date`, `official_id` (Pendeta), `location`, `witness_names`, `program_id` (pranikah, opsional), `certificate_number`, `issued_at`.
2. Sistem otomatis membuat **2 `member_sacraments` type=`nikah`** (per pasangan) dengan `marriage_id`.
3. Terbitkan **Akta Nikah** (§4).

### 3.7 Kematian
- **Jalur A (dari member):** di MemberResource, action "Catat Kematian" → form `member_deaths` (death_date, burial, official) → simpan → `member.status='meninggal'`.
- **Jalur B (dari event):** buat Event berkategori "Ibadah Pemakaman" (mis. EventCategory `ibadah_pemakaman`) + roster official (Pendeta). Dari event tsb bisa di-link ke `member_deaths.event_id` dan/atau mengubah status member.
- Tidak ada dokumen wajib; **Surat Keterangan Kematian** opsional (LOW, bisa ditambahkan nanti).

---

## 4. Dokumen yang Diterbitkan (format & konten)

**Format umum:** halaman Filament (mis. `LifecycleCluster/Pages/CetakDokumen`) + **blade view** dengan `@media print` CSS → tombol "Cetak / Simpan PDF" (pola `warta-jemaat.blade.php`). Kop gereja dari `Church` (nama, alamat, logo bila ada). Kolom `issued_at` + `certificate_number` ditampilkan. Tanda tangan: nama + jabatan `official` (Pendeta/penatua) yang mengisi `official_id`.

| Dokumen | Sumber data | Konten utama |
|---|---|---|
| **Akta Lahir** (`birth_records`) | member + birth_record + church | Kop gereja, no. akta, nama anak, jenis kelamin, tempat/tanggal lahir, anak ke-, nama ayah & ibu, tanggal terbit, tanda tangan |
| **Dokumen Baptis Anak** (`member_sacraments` type `baptis_anak`) | member + sacrament + official | Kop, no. sertifikat, nama anak, tanggal baptis, nama orang tua, nama Pendeta, tanda tangan |
| **Dokumen Baptis** (`baptis_dewasa`) / **Dokumen Sidi** (`sidi`) | member + sacrament + official + program (opsional) | Kop, no. sertifikat, nama, tanggal, Pendeta, keterangan program pra-sidi, tanda tangan |
| **Akta Nikah** (`marriages`) | marriage + 2 member + official | Kop, no. akta, nama pasangan (suami & istri), tanggal/lokasi pemberkatan, saksi, Pendeta, tanda tangan |

- Nomor dokumen = `certificate_number` (diisi manual / generate helper sederhana `{gereja}-{tahun}-{no}` — keputusan implementasi; default manual editable).
- **Penyimpanan file:** opsional. Bila perlu arsip, simpan PDF di `document_path` (storage disk `local`/`public`) saat cetak — **di luar scope utama** (asumsi A10).

---

## 5. Penjadwalan Bimbingan

- Satu `GuidanceProgram` = satu angkatan/kelompok; banyak `GuidanceSession` (pertemuan) dengan `session_at` (datetime) → tampil sebagai daftar/agenda.
- Peserta per sesi = `guidance_session_members` (pivot + `attended`).
- Rekap: jumlah pertemuan dihadiri per member; program `selesai` otomatis tersedia di dropdown penerbitan sakramen/marriage (`program_id`).
- Tidak ada recurring otomatis (asumsi A3 — jadwal berulang task terpisah); admin membuat sesi manual.

---

## 6. Status Anggota

| Event | Efek ke `members.status` |
|---|---|
| Kelahiran | member baru status `aktif` (default) |
| Baptis Anak / Sidi / Baptis Dewasa | **tidak berubah** |
| Pernikahan | **tidak berubah** (opsional ubah `family_relation` manual oleh admin) |
| Kematian | otomatis `meninggal` saat `member_deaths` dibuat |

---

## 7. RBAC

- Modul baru: **`lifecycle`** → permission keys `lifecycle.view/create/update/delete` + **`lifecycle.document.view`** (cetak dokumen). Tambahkan ke `RoleRegistry` matriks T3.
- Matriks (default, mengikuti T3):

| Role | `lifecycle` | `lifecycle.document` |
|---|---|---|
| `super_admin` | view+create+update+delete (lintas gereja) | view |
| `church_admin` | view+create+update+delete (gereja sendiri) | view |
| `finance_admin` | **ditolak** | **ditolak** |
| `jemaat_admin` | view (read-only) — keputusan owner opsional; default **ditolak** | **ditolak** |
| `warta_editor` | view (read-only, utk data warta) | view |
| `report_viewer` | view (read-only) | view |

- Policy baru: `BirthRecordPolicy`, `GuidanceProgramPolicy`, `GuidanceSessionPolicy`, `GuidanceSessionMemberPolicy`, `MarriagePolicy`, `DeathPolicy` — semua `extends TenantPolicy`, `$module = 'lifecycle'`.
- Semua keputusan akses di-server (Policy), URL langsung → 403; UI hanya menyembunyikan menu (lapisan kedua).

---

## 8. UI / Filament

- **Cluster `Lifecycle`** (nav: Lifecycle) dengan 4 resource:
  - `BirthRecordResource` (+ akses cepat dari MemberResource).
  - `GuidanceProgramResource` → tab **Sessions** (RelationManager: create/edit sesi, jadwal) → per sesi tab **Participants** (RelationManager: tambah member, toggle `attended`).
  - `MarriageResource` (+ otomasi 2 sakramen nikah).
  - `DeathResource` (+ action "Catat Kematian" di MemberResource; opsi buat dari Event pemakaman).
- Resource **Lifecycle** memakai pola existing: `TrashedFilter`, `RestoreAction`, `DeleteAction` (soft), `RestoreBulkAction`, badge `status`/`type`, `church_id` tersembunyi utk non-super_admin (global scope menjamin).
- Dokumen: satu halaman cetak per jenis (atau `CetakDokumenPage` dengan param) — **back-end + Filament cukup, tanpa frontend khusus** (pola Warta).

---

## 9. Acceptance Criteria (GIVEN / WHEN / THEN)

### Skema & model
- **AC-LC-01** GIVEN migrasi baru dijalankan, THEN tabel `birth_records`, `guidance_programs`, `guidance_sessions`, `guidance_session_members`, `marriages`, `member_deaths` ada lengkap dgn `church_id` NOT NULL + index, FK, `deleted_at`, timestamps; `member_sacraments` punya kolom baru `marriage_id`, `program_id`, `issued_at`, `document_path`.
- **AC-LC-02** GIVEN setiap model baru, THEN memakai `BelongsToChurch` + `SoftDeletes` + `RecordsAuditTrail`; `churchForeignKeyMap` & `deriveChurchIdFromParent` sesuai §2; **tidak ada** query manual `where('church_id', auth()...)` di kode baru.
- **AC-LC-03** GIVEN `UNIQUE(session_id, member_id)` × SoftDeletes, THEN penambahan peserta memakai **restore-or-create** (record soft-deleted di-restore, duplikat aktif dilewati, tidak ada partial index) — konsisten AC-T2-17/18.

### Alur & integrasi
- **AC-LC-04** GIVEN user membuat Marriage, THEN 2 `member_sacraments` type `nikah` dibuat otomatis (satu per pasangan) dgn `marriage_id` terisi; kedua record muncul di Warta minggu tsb.
- **AC-LC-05** GIVEN user membuat DeathRecord, THEN `members.status` berubah menjadi `meninggal`.
- **AC-LC-06** GIVEN program pra-sidi/pranikah berstatus `selesai`, THEN program tsb tersedia sebagai `program_id` saat menerbitkan sakramen/marriage.
- **AC-LC-07** GIVEN BirthRecord dibuat, THEN tanggal/tempat lahir default dari member, dan nama ayah/ibu default dari keluarga (dapat diedit) — tidak wajib (fallback string kosong bila keluarga tak punya kepala/istri).

### Dokumen
- **AC-LC-08** GIVEN user membuka halaman cetak (akta lahir / baptis anak / baptis / sidi / akta nikah), THEN blade merender konten sesuai §4 tanpa exception, dgn `@media print` + tombol cetak; data kosong (nullable) ditampilkan aman (tanpa crash).

### Tenant & keamanan
- **AC-LC-09** GIVEN admin gereja A, WHEN membuat record dengan FK milik gereja B (member/official/program/event), THEN ditolak 403 via `assertChurchForeignKeysConsistent`.
- **AC-LC-10** GIVEN `super_admin` membuat record pada gereja B, THEN `church_id` record = gereja B (via `deriveChurchIdFromParent`), bukan gereja aktor.
- **AC-LC-11** GIVEN admin gereja A, THEN list lifecycle default hanya menampilkan data gereja A (global scope).

### RBAC
- **AC-LC-12** GIVEN `finance_admin`, THEN akses cluster/URL lifecycle → 403 (ditolak).
- **AC-LC-13** GIVEN `church_admin`/`super_admin`, THEN dapat create/update/delete data lifecycle gereja sendiri (super_admin lintas gereja); URL langsung record gereja lain → 403 utk church_admin.
- **AC-LC-14** GIVEN `warta_editor`/`report_viewer`, THEN akses read-only (view) lifecycle; aksi tulis ditolak.

### Audit & soft delete
- **AC-LC-15** GIVEN create/update/delete/restore pada salah satu model baru, THEN `audit_logs` tercatat (action, user_id, church_id, old/new).
- **AC-LC-16** GIVEN record lifecycle di-soft-delete, THEN tidak muncul di list default tapi masih ada di DB (`withTrashed()`); restore mengembalikan tampilan.

### Test wajib (feature tests, pola `tests/Feature/*Test.php` + RefreshDatabase)
1. `test_birth_record_create_dan_akta_render` (AC-01, 07, 08)
2. `test_marriage_create_membuat_2_sakramen_nikah` (AC-04)
3. `test_death_record_mengubah_status_member` (AC-05)
4. `test_guidance_sesi_peserta_restore_or_create` (AC-03)
5. `test_guidance_program_selesai_tersedia_utk_penerbitan` (AC-06)
6. `test_lifecycle_cross_church_ditolak` (AC-09)
7. `test_lifecycle_super_admin_mengikuti_church_induk` (AC-10)
8. `test_lifecycle_scope_tenant_terisolasi` (AC-11)
9. `test_lifecycle_finance_admin_ditolak` (AC-12)
10. `test_lifecycle_soft_delete_dan_restore` (AC-16)
11. `test_lifecycle_audit_tercatat` (AC-15)
12. `test_dokumen_render_dengan_data_null_aman` (AC-08)

---

## 10. Asumsi Eksplisit

- **A1.** Cluster `Lifecycle` baru; `member_sacraments` tetap menjadi sumber kebenaran sakramen (agar Warta/riwayat sakramen existing otomatis terisi).
- **A2.** Baptis Dewasa & Sidi dianggap satu alur (bimbingan pra-sidi) → pemilihan type `sidi` vs `baptis_dewasa` saat penerbitan (sesuai kondisi member).
- **A3.** **Tidak ada jadwal berulang otomatis** untuk sesi bimbingan — admin membuat sesi manual (recurring task terpisah).
- **A4.** Kelahiran = member baru; tidak ada "tamu/anak belum jadi anggota" di scope ini.
- **A5.** Pernikahan hanya untuk 2 member yang terdaftar (bukan pasangan luar).
- **A6.** Baptis/Sidi/Nikah tidak mengubah `members.status` maupun `family_relation` secara otomatis.
- **A7.** `jemaat_admin` default **ditolak** lifecycle (keputusan owner bisa diubah via matriks T3 tanpa mengubah spec ini).
- **A8.** Hapus/restore `member_deaths` tidak mengembalikan `members.status` otomatis.
- **A9.** Dokumen memakai **blade + print CSS**, bukan library PDF; penyimpanan file PDF (`document_path`) opsional dan di luar scope utama.
- **A10.** Penerbitan dokumen tidak otomatis; user menekan "Cetak". Nomor dokumen (`certificate_number`) diisi manual (default editable).
- **A11.** Implementasi modul `lifecycle` bergantung pada RBAC T3 (`UserRole`/`Permission`/`RoleRegistry`/`User::hasPermission`); jika T3 belum diimplementasi saat Byte mulai, gunakan fallback `TenantPolicy::$allowedRoles` existing dengan catatan TODO sinkron ke T3.
- **A12.** `witness_names` (json) pada marriage cukup 2 kolom bebas teks — tidak perlu entitas saksi terpisah.

---

## Lampiran — File yang Dibuat/Diubah

**Baru:**
- Migrasi: `create_birth_records_table`, `create_guidance_programs_table`, `create_guidance_sessions_table`, `create_guidance_session_members_table`, `create_marriages_table`, `create_member_deaths_table`, `alter_member_sacraments_add_lifecycle_columns` (urutan tanggal `2026_03_1x_xxxxxx`)
- Model: `BirthRecord`, `GuidanceProgram`, `GuidanceSession`, `GuidanceSessionMember`, `Marriage`, `Death` (6)
- Factory: 6 factory (mengikuti pola existing)
- Policy: 6 policy `extends TenantPolicy` ($module = `lifecycle`)
- Filament: `Clusters/Lifecycle/LifecycleCluster.php`, `BirthRecordResource`, `GuidanceProgramResource` (+ RelationManagers Sessions/Participants), `MarriageResource`, `DeathResource`, halaman cetak dokumen + blade view
- Test: `tests/Feature/LifecycleTest.php`

**Ubah:**
- `app/Models/Member.php` — relasi `birthRecord()`, `death()`, helper `markDeceased()`
- `app/Models/MemberSacrament.php` — relasi `marriage()`, `program()`; tambah kolom baru di fillable
- `app/Models/Event.php` — (opsional) relasi `death()`
- `app/Filament/Clusters/Demographics/Resources/Members/MemberResource.php` — action "Catat Kematian" (+ akses cepat BirthRecord)
- `app/Support/RoleRegistry.php` (dari T3) — tambah modul `lifecycle`
- `app/Providers/AppServiceProvider.php` — daftarkan policy baru (auto-discovery Filament/Laravel umumnya otomatis, verifikasi saja)
