# Spesifikasi Teknis: Donasi & Persembahan Online (QRIS, Rekening Bank, dan Integrasi Transaksi Otomatis)

## 1. Latar Belakang & Tujuan
Gereja-gereja dalam lingkup Sinode GKSBS membutuhkan sarana persembahan digital (kolekte, perpuluhan, dana pembangunan, dana diakonia) yang transparan, mudah diakses jemaat, dan terintegrasi langsung dengan pembukuan kas jemaat.

Modul ini menyediakan:
1. **Pengaturan Rekening & QRIS Gereja**: Majelis dapat mengunggah barcode QRIS resmi dan mendaftarkan nomor rekening bank jemaat.
2. **Halaman Publik Persembahan (`/persembahan`)**: Halaman publik bergaya sakral-dignified (Caudex & Raleway, GKSBS green-blue) untuk jemaat/simpatisan memberikan persembahan dan konfirmasi bukti transfer.
3. **Portal Mandiri Anggota (`/portal/persembahan`)**: Warga jemaat terdaftar dapat melihat riwayat persembahan mereka serta mengisi form dengan profil otomatis.
4. **Verifikasi Majelis di Panel Filament (`OnlineOfferingResource`)**: Admin Keuangan / Church Admin dapat meninjau bukti transfer dan mengonfirmasi persembahan. Saat dikonfirmasi, sistem **secara otomatis membuat record `Transaction` penerimaan kas (`type: 'debit'`)** pada kantong kas (`Fund`) dan kategori (`FinancialCategory`) terkait.
5. **Keamanan & Isolasi Multi-Tenant**: Tenant isolation ketat per `church_id` serta audit trail otomatis.

---

## 2. Struktur Database & Model

### 2.1 Migrasi `churches`
Menambahkan kolom:
- `qris_image_path` (string, nullable)
- `bank_accounts` (json, nullable) -> array: `[{bank_name, account_number, account_holder}]`

### 2.2 Tabel `online_offerings`
- `id` (bigint unsigned, primary key)
- `church_id` (bigint unsigned, foreign key to `churches`)
- `member_id` (bigint unsigned, nullable, foreign key to `members`)
- `fund_id` (bigint unsigned, foreign key to `funds`)
- `financial_category_id` (bigint unsigned, foreign key to `financial_categories`)
- `donor_name` (string, default 'Hamba Allah')
- `donor_phone` (string, nullable)
- `donor_email` (string, nullable)
- `amount` (bigint unsigned)
- `payment_method` (string: `qris`, `bank_transfer`, `va`)
- `bank_name` (string, nullable)
- `reference_code` (string, unique)
- `proof_path` (string, nullable)
- `prayer_notes` (text, nullable)
- `status` (enum: `pending`, `confirmed`, `rejected`, default 'pending')
- `confirmed_by` (bigint unsigned, nullable, foreign key to `users`)
- `confirmed_at` (datetime, nullable)
- `transaction_id` (bigint unsigned, nullable, foreign key to `transactions`)
- `rejection_reason` (string, nullable)
- `timestamps` & `deleted_at`

---

## 3. Alur Kerja & Business Logic

### 3.1 Pengajuan Persembahan
1. Donor mengisi formulir (nama, nominal, pos kantong kas, kategori persembahan, bukti transfer, pokok doa).
2. Sistem meng-generate `reference_code` unik (format: `OFF-YYYYMMDD-XXXX`).
3. Record `OnlineOffering` disimpan dengan status `pending`.

### 3.2 Konfirmasi oleh Majelis / Finance Admin
1. Melalui `OnlineOfferingResource` di Filament (Cluster Finance), petugas melihat persembahan berstatus `pending`.
2. Petugas mengklik tombol **Konfirmasi**.
3. Sistem membuat baris `Transaction`:
   - `church_id`: sama dengan persembahan.
   - `fund_id`: sama dengan persembahan.
   - `category_id`: sama dengan persembahan.
   - `type`: `debit` (kas masuk).
   - `amount`: nominal persembahan.
   - `transaction_date`: tanggal konfirmasi (hari ini).
   - `description`: `"Persembahan Online [reference_code] dari [donor_name]"`.
4. Status `OnlineOffering` diubah menjadi `confirmed`, mencatat `confirmed_by`, `confirmed_at`, dan `transaction_id`.

### 3.3 Penolakan Persembahan
1. Jika bukti transfer tidak valid/salah nominal, petugas mengklik tombol **Tolak** dengan mengisi alasan penolakan.
2. Status diubah menjadi `rejected` beserta catatan `rejection_reason`.

---

## 4. Rencana File & Implementasi
1. Migrasi:
   - `database/migrations/2026_03_19_000001_add_offering_columns_to_churches_table.php`
   - `database/migrations/2026_03_19_000002_create_online_offerings_table.php`
2. Model:
   - `app/Models/OnlineOffering.php`
   - Update `app/Models/Church.php`
3. Filament:
   - `app/Filament/Clusters/Finance/Resources/OnlineOffering/OnlineOfferingResource.php`
   - Pages: `ListOnlineOfferings.php`, `ViewOnlineOffering.php`
   - Update `app/Filament/Clusters/System/Resources/Church/ChurchResource.php` (pengaturan QRIS & Rekening)
4. Controller & Views:
   - `app/Http/Controllers/PublicOfferingController.php`
   - `resources/views/public/offering/index.blade.php`
   - Update `app/Http/Controllers/Portal/MemberPortalWebController.php` & views
   - Update `app/Http/Controllers/Portal/MemberPortalApiController.php`
5. Routing:
   - `routes/web.php` & `routes/api.php`
6. Test Suite:
   - `tests/Feature/OnlineOfferingTest.php`
