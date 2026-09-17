# SPESIFIKASI TEKNIS: CMS WEBSITE RESMI GEREJA & MODUL MIGRASI DATA EXCEL TERPADU

- **Dokumen:** `analisis/SPEC-CMS-LANDING-DAN-IMPORT-EXCEL.md`
- **Versi:** v1.0 (Final Architecture Spec — Siap Implementasi)
- **Penulis:** Ada (Business Analyst / Technical Spec Writer)
- **Target Implementer:** Byte (Backend Engineer) & Pixel (Frontend Engineer)
- **Reviewer:** Nova (QA Lead), Vera (Security & Multi-Tenant Lead), Ray (DevOps Lead)
- **Base Branch:** master (`5d0daa0`)
- **Status:** APPROVED FOR IMPLEMENTATION

---

## 0. Konteks Bisnis & Pergeseran Paradigma

### 0.1 Pergeseran dari "SaaS Komersial" Menjadi "Website Resmi Pelayanan Gereja"
1. **Bukan Aplikasi Komersial**: Sistem *Portal Gereja* **BUKAN** produk komersial/SaaS untuk dijual, melainkan karya sukarela untuk menunjang peribadahan, penggembalaan, dan tata kelola jemaat gereja lokal.
2. **Identitas Halaman Depan (`/`)**:
   - Selama ini halaman `/` (`resources/views/welcome.blade.php`) menampilkan materi layaknya promosi startup software (copywriting "Pintu Layanan", "Fitur & Modul", "Sistem Informasi & Administrasi Jemaat").
   - **Kebutuhan Nyata**: Halaman muka (`/`) harus menjadi **Website Resmi Gereja Pusat / Jemaat Induk** (contoh konkret: **GKSBS Filadelfia** dengan cabang pos pelayanan/kelompok: **Candimas**, **Trimulyo**, dan **Margomulyo**).
   - Pengunjung publik, jemaat, maupun simpatisan yang membuka website langsung disambut dengan identitas gereja, tema tahunan, renungan/sambutan majelis, visi-misi, jadwal ibadah, serta tautan warta jemaat tiap cabang.
   - Pintu masuk portal diletakkan dengan elegan di header:
     - **Portal Jemaat** (`/portal/login`): Bagi warga jemaat untuk cek profil, jadwal pelayanan, dan warta pribadi.
     - **Area Majelis** (`/admin/login`): Bagi presbiter/majelis dan administrator gereja.

### 0.2 Urgensi Modul Migrasi Data Excel Terpadu (Data Migration Hub)
1. **Realitas Gereja Lokal**: Sebelum sistem ini ada, gereja mengelola puluhan lembar spreadsheet Excel (`.xlsx`) manual untuk data keluarga, warga jemaat, kas tunai, pelayan, dan jadwal ibadah.
2. **Bottleneck Onboarding**: Menginput ratusan kepala keluarga dan warga jemaat satu per satu lewat form web admin akan memakan waktu berminggu-minggu dan rentan kesalahan manusia (*human error*).
3. **Solusi Definitif**: Modul **Migrasi Data Excel Terpadu** berbasis `maatwebsite/excel` (yang sudah terpasang pada `composer.json`), menyediakan:
   - Template resmi Excel (`.xlsx`) berformat rapi dengan validasi dan petunjuk kamus nilai.
   - Engine import cerdas dengan validasi baris, deduplikasi NIK, isolasi tenant (`church_id`), pengelompokan otomatis Kartu Keluarga (KK), pembuatan rekam sakramen otomatis, dan laporan feedback error terperinci.
   - Menu terpadu di Filament: **"Migrasi Data" (Data Migration Hub)**.

---

## 1. Arsitektur CMS Website Resmi Gereja

### 1.1 Diagram Relasi Entitas & Data Flow CMS
```
+-------------------------------------------------------------+
|                      LANDING PAGE ('/')                     |
|                                                             |
|  [Header: Logo | Brand Induk | Portal Jemaat | Area Majelis]|
|  [Hero Banner: Nama Gereja | Tagline | Ayat Tema Tahunan]   |
|  [Sambutan Gembala / Majelis Jemaat & Foto]                 |
|  [Profil Singkat, Visi & Misi]                              |
|  [Pos Pelayanan / Kelompok Cabang (Candimas, Trimulyo, dll)]|
|  [Jadwal Ibadah Induk & Cabang]                             |
|  [Kontak Sekretariat & Peta Lokasi]                         |
+-------------------------------------------------------------+
                            |
           +----------------+----------------+
           |                                 |
           v                                 v
+-----------------------+         +-----------------------+
|   landing_settings    |         |        churches       |
| (Konten Induk/Pusat)  |         |  (Cabang/Pos Layanan) |
| - hero_title          |         | - name, code          |
| - theme_verse         |         | - address, phone      |
| - pastoral_message    |         | - email, website      |
| - vision, mission     |         | - logo_path           |
| - default_schedules   |         | => link /warta/{code} |
+-----------------------+         +-----------------------+
           ^                                 ^
           |                                 |
+-----------------------+         +-----------------------+
|  Filament Admin Panel |         |  Filament Admin Panel |
|   (Super Admin CMS)   |         | (Church Admin Cabang) |
+-----------------------+         +-----------------------+
```

### 1.2 Skema Database: Tabel `landing_settings`
Buat file migrasi baru:
`database/migrations/2026_03_18_000001_create_landing_settings_table.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_settings', function (Blueprint $table): void {
            $table->id();
            // church_id nullable: NULL = Pengaturan Utama Gereja Induk / Pusat.
            // Jika gereja cabang memiliki custom domain/landing page di kemudian hari, dapat diisi church_id.
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->index('church_id');

            // 1. Hero & Tema Tahunan
            $table->string('hero_title')->default('GKSBS Filadelfia');
            $table->string('hero_subtitle')->nullable()->default('Gereja Kristen Sumatera Bagian Selatan');
            $table->string('hero_tagline')->default('Bertumbuh dalam Iman, Berakar dalam Kasih, Berbuah bagi Sesama');
            $table->text('theme_verse')->comment('Teks lengkap ayat tema tahunan beserta referensi (misal: Roma 12:10)');
            $table->string('theme_verse_ref')->nullable()->default('Roma 12:10');
            $table->string('hero_banner_path')->nullable()->comment('Path gambar banner hero di storage public');

            // 2. Sambutan Gembala / Majelis Jemaat
            $table->string('pastoral_greeting_title')->default('Salam Kasih dari Majelis Jemaat');
            $table->string('pastoral_greeting_author')->default('Pdt. Samuel Kriswanto, M.Th.');
            $table->string('pastoral_greeting_author_role')->default('Ketua Majelis Jemaat Induk');
            $table->string('pastoral_greeting_photo_path')->nullable();
            $table->longText('pastoral_greeting_content');

            // 3. Profil, Visi, Misi
            $table->longText('about_summary')->comment('Ringkasan sejarah dan profil gereja');
            $table->text('vision')->comment('Visi gereja');
            $table->json('missions')->nullable()->comment('Array daftar butir-butir misi');

            // 4. Jadwal Ibadah Induk & Kontak
            $table->json('worship_schedules')->nullable()->comment('Array terstruktur jadwal ibadah rutin induk');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('contact_address')->nullable();
            $table->text('contact_maps_url')->nullable()->comment('URL Google Maps atau link embed');
            $table->json('social_links')->nullable()->comment('JSON key-value: facebook, youtube, instagram, whatsapp');

            // 5. Kontrol Publikasi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_settings');
    }
};
```

### 1.3 Model Eloquent `App\Models\LandingSetting`
Buat file `app/Models/LandingSetting.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class LandingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'hero_title',
        'hero_subtitle',
        'hero_tagline',
        'theme_verse',
        'theme_verse_ref',
        'hero_banner_path',
        'pastoral_greeting_title',
        'pastoral_greeting_author',
        'pastoral_greeting_author_role',
        'pastoral_greeting_photo_path',
        'pastoral_greeting_content',
        'about_summary',
        'vision',
        'missions',
        'worship_schedules',
        'contact_email',
        'contact_phone',
        'contact_address',
        'contact_maps_url',
        'social_links',
        'is_active',
    ];

    protected $casts = [
        'missions' => 'array',
        'worship_schedules' => 'array',
        'social_links' => 'array',
        'is_active' => 'boolean',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Ambil pengaturan landing page yang aktif (dengan auto-caching).
     */
    public static function current(?int $churchId = null): self
    {
        $cacheKey = 'landing_setting_'.($churchId ?? 'central');

        return Cache::rememberForever($cacheKey, function () use ($churchId) {
            $record = self::query()
                ->where('church_id', $churchId)
                ->where('is_active', true)
                ->first();

            if ($record) {
                return $record;
            }

            // Fallback default bila belum diisi
            return self::createDefault($churchId);
        });
    }

    /**
     * Bersihkan cache saat record diubah atau disimpan.
     */
    public static function booted(): void
    {
        static::saved(function (self $setting): void {
            Cache::forget('landing_setting_'.($setting->church_id ?? 'central'));
        });

        static::deleted(function (self $setting): void {
            Cache::forget('landing_setting_'.($setting->church_id ?? 'central'));
        });
    }

    public function getHeroBannerUrlAttribute(): ?string
    {
        return $this->hero_banner_path ? Storage::disk('public')->url($this->hero_banner_path) : null;
    }

    public function getPastoralPhotoUrlAttribute(): ?string
    {
        return $this->pastoral_greeting_photo_path ? Storage::disk('public')->url($this->pastoral_greeting_photo_path) : null;
    }

    public static function createDefault(?int $churchId = null): self
    {
        return self::firstOrCreate(
            ['church_id' => $churchId],
            [
                'hero_title' => 'GKSBS Filadelfia',
                'hero_subtitle' => 'Gereja Kristen Sumatera Bagian Selatan — Klasis Tulang Bawang',
                'hero_tagline' => 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus',
                'theme_verse' => 'Hendaklah kamu saling mengasihi sebagai saudara dan saling mendahului dalam memberi hormat.',
                'theme_verse_ref' => 'Roma 12:10',
                'pastoral_greeting_title' => 'Salam Kasih & Damai Sejahtera Kristus',
                'pastoral_greeting_author' => 'Pdt. Samuel Kriswanto, M.Th.',
                'pastoral_greeting_author_role' => 'Ketua Majelis Jemaat GKSBS Filadelfia',
                'pastoral_greeting_content' => "Selamat datang di Website Resmi GKSBS Filadelfia. Kami bersyukur atas kasih karunia Tuhan yang terus memelihara persekutuan jemaat induk dan seluruh jemaat kelompok (Candimas, Trimulyo, Margomulyo). Mari bersama kita terus berakar di dalam Kristus, bertumbuh dalam iman, dan berbuah lebat bagi masyarakat sekitar.",
                'about_summary' => "GKSBS Filadelfia adalah persekutuan jemaat yang berpusat pada Kristus, melayani warga jemaat di wilayah Lampung Tengah dan sekitarnya melalui jemaat induk dan pos-pos pelayanan kelompok jemaat secara terpadu dan guyub.",
                'vision' => 'Terwujudnya jemaat yang mandiri, misioner, berwawasan oikumenis, serta menjadi berkat nyata bagi sesama ciptaan Tuhan.',
                'missions' => [
                    'Menyelenggarakan peribadahan dan pembinaan rohani yang berakar pada Firman Allah.',
                    'Mempererat persaudaraan dan solidaritas antar jemaat kelompok secara guyub dan inklusif.',
                    'Mengembangkan karya pelayanan diakonia holistik bagi jemaat dan masyarakat luas.',
                    'Mewujudkan tata kelola organisasi dan pelayanan gereja yang transparan, akuntabel, dan tertib.',
                ],
                'worship_schedules' => [
                    ['name' => 'Ibadah Raya Minggu Induk', 'day' => 'Minggu', 'time' => '08:30 WIB', 'location' => 'Gedung Gereja Utama'],
                    ['name' => 'Kebaktian Anak / Sekolah Minggu', 'day' => 'Minggu', 'time' => '08:30 WIB', 'location' => 'Gedung Serbaguna'],
                    ['name' => 'Ibadah Pemuda & Remaja', 'day' => 'Sabtu', 'time' => '17:00 WIB', 'location' => 'Ruang Pemuda'],
                    ['name' => 'Persekutuan Doa & Pendalaman Alkitab', 'day' => 'Rabu', 'time' => '18:30 WIB', 'location' => 'Gedung Gereja'],
                ],
                'contact_email' => 'sekretariat@gksbs-filadelfia.org',
                'contact_phone' => '0812-7200-1234',
                'contact_address' => 'Jl. Gereja Filadelfia No. 01, Kel. Candimas, Kec. Natar, Lampung Selatan',
                'contact_maps_url' => 'https://maps.google.com/?q=GKSBS+Filadelfia',
                'social_links' => [
                    'youtube' => 'https://youtube.com/@gksbsfiladelfia',
                    'facebook' => 'https://facebook.com/gksbsfiladelfia',
                    'instagram' => 'https://instagram.com/gksbsfiladelfia',
                ],
                'is_active' => true,
            ]
        );
    }
}
```

### 1.4 Pengelolaan CMS di Filament Admin Panel
- **Letak Halaman**: `app/Filament/Clusters/System/Pages/ManageLandingPage.php` (Grup Menu: **System / Pengaturan Website**).
- **Hak Akses (RBAC)**:
  - `super_admin`: Akses penuh (View & Update) untuk konten pusat Gereja Induk.
  - `church_admin`: Read-only untuk melihat data pusat, namun diarahkan untuk mengelola identitas cabang (nama, telepon, alamat, website, logo) via menu `ChurchResource` / Profil Cabang.
- **Skema Form Filament (Rich Form Experience)**:
  1. **Section 1: Hero Banner & Ayat Tema**:
     - `hero_title` (TextInput, required)
     - `hero_subtitle` (TextInput, nullable)
     - `hero_tagline` (TextInput, required)
     - `theme_verse` (Textarea, required)
     - `theme_verse_ref` (TextInput, placeholder: 'Roma 12:10')
     - `hero_banner_path` (FileUpload, disk: 'public', directory: 'landing/banners', image, imageEditor, max 2MB)
  2. **Section 2: Sambutan Gembala / Majelis Jemaat**:
     - `pastoral_greeting_title` (TextInput, required)
     - `pastoral_greeting_author` (TextInput, required)
     - `pastoral_greeting_author_role` (TextInput, required)
     - `pastoral_greeting_photo_path` (FileUpload, disk: 'public', directory: 'landing/pastoral', avatar, circleCropper, max 1MB)
     - `pastoral_greeting_content` (RichEditor / Textarea rows 6, required)
  3. **Section 3: Profil, Visi & Misi**:
     - `about_summary` (Textarea rows 4, required)
     - `vision` (Textarea rows 2, required)
     - `missions` (Repeater: `TextInput::make('item')->label('Butir Misi')`, minItems: 1)
  4. **Section 4: Jadwal Ibadah Induk**:
     - `worship_schedules` (Repeater: `name`, `day`, `time`, `location`)
  5. **Section 5: Kontak & Media Sosial**:
     - `contact_email`, `contact_phone`, `contact_address`, `contact_maps_url`
     - `social_links` (KeyValue: YouTube, Facebook, Instagram, WhatsApp)

### 1.5 Spesifikasi Tampilan Publik (`resources/views/welcome.blade.php`)
Desain responsif, bernuansa hangat (*reverent, modern, warm slate & amber/emerald*), bebas dari unsur jualan software:

1. **Header & Sticky Navbar**:
   - Logo Gereja Induk (fallback: ikon salib bernuansa hangat).
   - Nama Gereja ("GKSBS Filadelfia") & Subjudul Klasis ("Klasis Tulang Bawang").
   - Navigasi Cepat (Smooth Anchor): *Beranda, Sambutan, Profil & Visi, Pos Pelayanan, Jadwal Ibadah, Kontak*.
   - **Dua Tombol Aksi Terpisah di Kanan**:
     - `[Portal Jemaat]` (`/portal/login`): Desain tombol hangat beraksen outline border abu/slate dengan ikon user/hati, ramah bagi warga jemaat biasa.
     - `[Area Majelis]` (`/admin/login`): Desain tombol tegas berwarna solid slate/amber dengan ikon kunci/perisai untuk presbiter dan admin.
2. **Hero & Tema Tahunan**:
   - Banner besar dengan overlay gelap tipis untuk keterbacaan teks.
   - Badge: *"Ayat Tema Tahunan 2026"*.
   - Tipografi Ayat Tema dengan kutipan Alkitab yang menyejukkan.
3. **Sambutan Majelis Jemaat / Pendeta**:
   - Tata letak 2 kolom: Foto Pendeta/Ketua Majelis dengan pigura melengkung halus dan pesan gembala resmi.
4. **Profil Singkat, Visi & Misi**:
   - 3 kartu ringkas: Sejarah/Profil, Visi, dan Misi (daftar berbutir rapi).
5. **Pos Pelayanan & Jemaat Kelompok Cabang (Candimas, Trimulyo, Margomulyo)**:
   - Menampilkan kartu dinamis dari tabel `churches` (`Church::all()`):
     - Nama Kelompok (misal: "Jemaat Kelompok Candimas", "Jemaat Kelompok Trimulyo", "Jemaat Kelompok Margomulyo").
     - Alamat Fisik & Kontak Sekretariat Cabang.
     - Tombol Langsung: `[Baca Warta Jemaat]` -> link ke `route('public.warta.index', $church->code)`.
6. **Jadwal Ibadah Raya & Kategorial**:
   - Tabel/grid jadwal ibadah (Ibadah Umum, Sekolah Minggu, Pemuda, Kaum Ibu/Bapak, Doa Rumah Tangga).
7. **Kontak Sekretariat & Peta Lokasi**:
   - Alamat sekretariat, jam pelayanan kantor gereja, kontak darurat pastoral, tombol navigasi Google Maps.
8. **Footer Resmi**:
   - Pernyataan Sinodal: *"Anggota Persekutuan Gereja-gereja di Indonesia (PGI) — Sinode GKSBS"*.
   - Hak Cipta & Disclaimer: Pelayanan sukarela untuk kemuliaan nama Tuhan.

---

## 2. Arsitektur Modul Migrasi Data Excel Terpadu (Data Migration Hub)

### 2.1 Konsep & Alur Kerja Migrasi
Gereja yang baru beralih dari Excel ke portal sistem membutuhkan proses migrasi 3 langkah:
```
[ Langkah 1: Unduh Template ]  -->  [ Langkah 2: Pengisian Excel ]  -->  [ Langkah 3: Upload & Validasi ]
    Download .xlsx resmi                 Pengurus gereja mengisi             Sistem validasi baris,
    dengan petunjuk nilai                data offline di spreadsheet         isolasi tenant, upsert, &
    dan contoh baris nyata               sesuai format                       beri laporan detail
```

### 2.2 Entitas yang Didukung dalam Migrasi

| No | Modul Migrasi | Target Tabel Terkait | Kebutuhan Bisnis Gereja |
|---|---|---|---|
| **A** | **Jemaat & Keluarga** | `families`, `members`, `member_sacraments` | Impor seluruh warga jemaat, pengelompokan nomor KK, relasi keluarga, kontak, dan riwayat sakramen (Baptis, Sidi, Nikah). |
| **B** | **Master Keuangan** | `funds`, `financial_categories` | Impor pos kantong/kas (Kas Umum, Pembangunan, Diakonia) dan kategori transaksi (debit/credit). |
| **C** | **Master Pelayan & Pejabat** | `ministry_roles`, `officials` | Impor jabatan rohani (Pendeta, Penatua, Diaken, Pemusik) dan pejabat gereja beserta masa baktinya. |
| **D** | **Master Acara & Jadwal** | `event_categories`, `recurring_schedules` | Impor kategori ibadah dan template jadwal rutin mingguan/bulanan. |

---

## 3. Spesifikasi Detail Template & Importer Per Modul

### 3.1 Modul A: Jemaat & Keluarga (Demographics & Sacraments)

#### 3.1.1 Struktur Kolom Template Excel (`template-jemaat-keluarga.xlsx`)
File template multi-sheet:
- **Sheet 1: `Data Jemaat`**
- **Sheet 2: `Petunjuk & Kamus Nilai`**

Daftar Kolom Sheet 1:
1. `no_kk` (Wajib, Text): Nomor Kartu Keluarga (misal: `KK-001` atau `1801010101000001`).
2. `nama_keluarga` (Opsional, Text): Nama Keluarga (misal: `Keluarga Budi Santoso`). Jika kosong, otomatis diisi dari nama kepala keluarga.
3. `alamat` (Wajib, Text): Alamat domisili keluarga jemaat.
4. `nik` (Opsional/Dianjurkan, Text 16 digit): Nomor Induk Kependudukan / No KTP.
5. `nama_lengkap` (Wajib, Text): Nama lengkap jemaat (misal: `Budi Santoso`).
6. `jenis_kelamin` (Wajib, Enum): `L` atau `P` (diterima juga: `Laki-laki`, `Perempuan`, `m`, `f`).
7. `tempat_lahir` (Opsional, Text): Kota kelahiran (misal: `Lampung Tengah`).
8. `tanggal_lahir` (Opsional/Dianjurkan, Date): Format `YYYY-MM-DD` atau `DD/MM/YYYY` (misal: `1985-05-17`).
9. `hubungan_keluarga` (Wajib, Enum): `kepala_keluarga`, `istri`, `anak`, `lainnya`.
10. `nomor_telepon` (Opsional, Text): Nomor WhatsApp / Telepon seluler (misal: `081272001234`). Disimpan ke `custom_fields['phone']`.
11. `status_anggota` (Wajib, Enum): `aktif`, `titipan`, `pindah`, `meninggal` (Default: `aktif`).
12. `status_baptis` (Opsional, Enum/Boolean): `sudah` atau `belum`.
13. `tanggal_baptis` (Opsional, Date): Format `YYYY-MM-DD`.
14. `nomor_surat_baptis` (Opsional, Text): Nomor akta baptis jika ada.
15. `status_sidi` (Opsional, Enum/Boolean): `sudah` atau `belum`.
16. `tanggal_sidi` (Opsional, Date): Format `YYYY-MM-DD`.
17. `nomor_surat_sidi` (Opsional, Text): Nomor akta sidi jika ada.
18. `status_nikah` (Opsional, Enum/Boolean): `sudah` atau `belum`.
19. `tanggal_nikah` (Opsional, Date): Format `YYYY-MM-DD`.
20. `nomor_surat_nikah` (Opsional, Text): Nomor akta nikah gereja jika ada.

#### 3.1.2 Aturan Bisnis & Logika Importer (`App\Imports\MemberFamilyImport`)
1. **Multi-Tenancy Isolation**:
   - `church_id` diambil mutlak dari user login (`auth()->user()->church_id`).
   - Jika login sebagai `super_admin`, `church_id` wajib ditentukan melalui parameter/dropdown target gereja sebelum import dimulai.
2. **Pengelompokan Otomatis Keluarga (`Family`)**:
   - Sebelum memproses anggota, sistem mencari `Family::where('church_id', $churchId)->where('family_number', $row['no_kk'])->first()`.
   - Jika belum ada, sistem membuat record `Family` baru dengan `name = $row['nama_keluarga'] ?: 'Kel. ' . $row['nama_lengkap']` dan `address = $row['alamat']`.
   - Jika sudah ada, sistem mengaitkan anggota dengan `family_id` yang ditemukan dan memperbarui alamat jika alamat keluarga sebelumnya masih kosong.
3. **Pencarian & Upsert Anggota (`Member`)**:
   - Kunci unik identifikasi anggota:
     - Jika `nik` terisi: cari berdasarkan `(church_id, id_card_number)`.
     - Jika `nik` kosong: cari berdasarkan kombinasi `(church_id, family_id, full_name, birth_date)`.
   - Jika ditemukan, lakukan **UPDATE** field identitas (upsert aman tanpa duplikasi).
   - Jika tidak ditemukan, buat record **BARU**.
4. **Pencegahan Tabrakan NIK**:
   - NIK divalidasi tidak boleh dipakai oleh anggota di gereja lain (integritas unik sistem), atau jika NIK ganda terdeteksi dalam baris file Excel yang sama, catat sebagai error baris.
5. **Pembuatan Otomatis Catatan Sakramen (`member_sacraments`)**:
   - **Baptis**: Jika `status_baptis == 'sudah'` atau `tanggal_baptis` terisi:
     - Tentukan tipe sakramen: jika usia saat baptis < 15 tahun (atau tgl lahir kosong) -> `baptis_anak`, selain itu -> `baptis_dewasa`.
     - Cek apakah sudah ada `MemberSacrament` untuk member tsb dengan tipe baptis. Jika belum, buat dengan `sacrament_date = $row['tanggal_baptis'] ?: now()`, `certificate_number = $row['nomor_surat_baptis']`.
   - **Sidi**: Jika `status_sidi == 'sudah'` atau `tanggal_sidi` terisi:
     - Buat `MemberSacrament` tipe `sidi` jika belum ada.
   - **Nikah**: Jika `status_nikah == 'sudah'` atau `tanggal_nikah` terisi:
     - Buat `MemberSacrament` tipe `nikah` jika belum ada.

---

### 3.2 Modul B: Master Keuangan (Pos Kas/Dana & Kategori Finansial)

#### 3.2.1 Struktur Kolom Template Excel (`template-master-keuangan.xlsx`)
Multi-sheet:
- **Sheet 1: `Pos Kas & Dana`**:
  1. `nama_pos_dana` (Wajib, Text): Nama pos kas/dana (misal: `Kas Umum Operasional`, `Dana Pembangunan Gedung`, `Dana Diakonia / Sosial`, `Kas Komisi Pemuda`).
- **Sheet 2: `Kategori Transaksi`**:
  1. `nama_kategori` (Wajib, Text): Nama pos transaksi (misal: `Kolekte Ibadah Hari Minggu`, `Persembahan Persepuluhan`, `Biaya Listrik PLN & Air PAM`, `Bantuan Warga Sakit`).
  2. `tipe` (Wajib, Enum): `Pemasukan` (Debit) atau `Pengeluaran` (Kredit).
- **Sheet 3: `Petunjuk & Kamus Nilai`**

#### 3.2.2 Aturan Bisnis & Logika Importer (`App\Imports\FinanceMasterImport`)
1. **Normalisasi Tipe Finansial**:
   - Nilai input `Pemasukan`, `pemasukan`, `Masuk`, `debit`, `in`, `Debit` -> dinormalisasi menjadi **`debit`**.
   - Nilai input `Pengeluaran`, `pengeluaran`, `Keluar`, `credit`, `out`, `Kredit` -> dinormalisasi menjadi **`credit`**.
   - Nilai selain di atas ditolak dengan pesan error validasi yang jelas.
2. **Prinsip Cash Flow Tunai Murni**:
   - Tidak ada kolom rekening bank, nomor giro, atau bank penerima. Sesuai arsitektur finansial sistem (100% kas tunai per kantong).
3. **Idempotensi & Anti-Duplikasi**:
   - Gunakan `Fund::firstOrCreate(['church_id' => $churchId, 'name' => trim($name)])`.
   - Gunakan `FinancialCategory::firstOrCreate(['church_id' => $churchId, 'name' => trim($name), 'type' => $normalizedType])`.

---

### 3.3 Modul C: Master Pelayan & Pejabat Gereja

#### 3.3.1 Struktur Kolom Template Excel (`template-master-pelayan.xlsx`)
Multi-sheet:
- **Sheet 1: `Jabatan Pelayanan`**:
  1. `nama_jabatan` (Wajib, Text): Misal `Pendeta Jemaat`, `Penatua`, `Diaken`, `Pemusik / Organis`, `Song Leader`, `Guru Sekolah Minggu`, `Multimedia`.
- **Sheet 2: `Pejabat Gereja`**:
  1. `tipe_pejabat` (Wajib, Enum): `majelis_lokal`, `pendeta_internal`, `pelayan_tamu`.
  2. `nik_anggota` (Opsional untuk pelayan tamu, Text): NIK warga jemaat untuk mengaitkan ke tabel `members`.
  3. `nama_pejabat` (Wajib jika NIK kosong / pelayan tamu, Text): Nama lengkap pejabat.
  4. `gereja_asal` (Opsional, Text): Nama gereja/sinode asal (wajib jika `tipe_pejabat == pelayan_tamu`).
  5. `tanggal_mulai_jabatan` (Wajib, Date): Format `YYYY-MM-DD`.
  6. `tanggal_selesai_jabatan` (Opsional, Date): Format `YYYY-MM-DD` (kosong = aktif menjabat).
- **Sheet 3: `Petunjuk & Kamus Nilai`**

#### 3.3.2 Aturan Bisnis & Logika Importer (`App\Imports\OfficialMinistryImport`)
1. **Resolusi Anggota**:
   - Jika `nik_anggota` terisi, cari `Member::where('church_id', $churchId)->where('id_card_number', $nik)->first()`.
   - Jika tidak ditemukan dan tipe adalah `majelis_lokal` atau `pendeta_internal`, sistem mencoba mencari berdasarkan kesamaan `full_name`. Jika tetap tidak ditemukan, baris dicatat sebagai peringatan/error.
   - Jika tipe adalah `pelayan_tamu`, simpan langsung ke kolom `officials.external_name` dan `officials.origin_church`.
2. **Integritas Relasi**:
   - Pastikan `member_id` yang ditautkan mutlak berasal dari `church_id` yang sama (menjamin kepatuhan aturan tenant Vera).

---

### 3.4 Modul D: Master Acara & Jadwal Berulang

#### 3.4.1 Struktur Kolom Template Excel (`template-master-acara-jadwal.xlsx`)
Multi-sheet:
- **Sheet 1: `Kategori Acara`**:
  1. `nama_kategori` (Wajib, Text): Misal `Ibadah Hari Minggu`, `Persekutuan Doa`, `Kebaktian Rumah Tangga`, `Ibadah Pemuda`, `Rapat Majelis`.
- **Sheet 2: `Jadwal Berulang`**:
  1. `kategori` (Wajib, Text): Nama kategori acara (harus cocok dengan salah satu kategori).
  2. `judul_jadwal` (Wajib, Text): Misal `Ibadah Raya Minggu Pagi`.
  3. `lokasi` (Wajib, Text): Gedung/ruangan/rumah jemaat.
  4. `frekuensi` (Wajib, Enum): `weekly`, `monthly`, `daily` (menerima bahasa Indonesia: `mingguan`, `bulanan`, `harian`).
  5. `hari_pelaksanaan` (Wajib untuk mingguan, Text): `Minggu`, `Senin`, `Selasa`, `Rabu`, `Kamis`, `Jumat`, `Sabtu` (diterima juga angka `0` s/d `6`).
  6. `jam_mulai` (Wajib, Time): Format `HH:MM` (misal: `09:00`).
  7. `jam_selesai` (Wajib, Time): Format `HH:MM` (misal: `11:00`).
  8. `tanggal_mulai_berlaku` (Wajib, Date): Format `YYYY-MM-DD`.
  9. `keterangan` (Opsional, Text): Catatan perlengkapan atau tema.
- **Sheet 3: `Petunjuk & Kamus Nilai`**

#### 3.4.2 Aturan Bisnis & Logika Importer (`App\Imports\EventScheduleImport`)
1. **Pemetaan Hari**:
   - `Minggu` / `Sunday` / `0` -> `[0]`
   - `Senin` / `Monday` / `1` -> `[1]`
   - `Selasa` / `Tuesday` / `2` -> `[2]`
   - `Rabu` / `Wednesday` / `3` -> `[3]`
   - `Kamis` / `Thursday` / `4` -> `[4]`
   - `Jumat` / `Friday` / `5` -> `[5]`
   - `Sabtu` / `Saturday` / `6` -> `[6]`
   - Disimpan ke kolom JSON `recurring_schedules.days_of_week`.
2. **Kategori Dinamis**:
   - Jika nama kategori belum ada di database, otomatis dibuatkan di `event_categories` sebelum membuat record `recurring_schedules`.

---

## 4. Spesifikasi Kelas Teknis: Exports & Imports (Maatwebsite/Excel)

### 4.1 Kelas Template Exporter (`App\Exports\Templates\...`)
Setiap template diexport menggunakan format Excel biner `.xlsx` dengan styling seragam:
- Header baris 1: Background `#1F4E78` (Navy Gereja), font putih, tebal, teks rata tengah.
- Freeze pane pada baris 1 (`$sheet->freezePane('A2')`).
- Auto column width untuk semua kolom.
- Sheet petunjuk berisi tabel definisi format tanggal (`YYYY-MM-DD`), pilihan enum yang sah, dan contoh baris data valid.

Daftar File Template Exporter:
1. `app/Exports/Templates/MemberFamilyTemplateExport.php`
2. `app/Exports/Templates/FinanceMasterTemplateExport.php`
3. `app/Exports/Templates/OfficialMinistryTemplateExport.php`
4. `app/Exports/Templates/EventScheduleTemplateExport.php`

Contoh implementasi `MemberFamilyTemplateExport.php`:
```php
<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class MemberFamilyTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Data Jemaat' => new MemberFamilyDataSheet(),
            'Petunjuk Pengisian' => new MemberFamilyGuideSheet(),
        ];
    }
}
```

### 4.2 Kelas Importer (`App\Imports\...`)
Setiap importer wajib mengimplementasikan:
- `Maatwebsite\Excel\Concerns\ToCollection` (atau `WithMultipleSheets`)
- `Maatwebsite\Excel\Concerns\WithHeadingRow`
- `Maatwebsite\Excel\Concerns\WithValidation` (atau validasi manual per baris dengan pelaporan komprehensif)
- `Maatwebsite\Excel\Concerns\SkipsEmptyRows`
- `Maatwebsite\Excel\Concerns\WithChunkReading` (chunk size: 250 baris untuk efisiensi memori)

Daftar File Importer:
1. `app/Imports/MemberFamilyImport.php`
2. `app/Imports/FinanceMasterImport.php`
3. `app/Imports/OfficialMinistryImport.php`
4. `app/Imports/EventScheduleImport.php`

### 4.3 DTO Hasil Validasi & Feedback Report: `ImportSummaryDTO`
Buat kelas `app/DTO/ImportSummaryDTO.php` untuk menampung feedback hasil eksekusi:

```php
<?php

declare(strict_types=1);

namespace App\DTO;

final class ImportSummaryDTO
{
    /**
     * @param array<int, array{row: int, column: string, value: mixed, message: string}> $errors
     */
    public function __construct(
        public readonly int $totalRowsRead,
        public readonly int $totalCreated,
        public readonly int $totalUpdated,
        public readonly int $totalSkipped,
        public readonly array $errors = [],
    ) {}

    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    public function hasFailures(): bool
    {
        return ! empty($this->errors);
    }
}
```

---

## 5. Antarmuka Pengguna di Filament: Halaman "Migrasi Data" (Data Migration Hub)

### 5.1 Spesifikasi Halaman Filament
- **Class**: `App\Filament\Pages\DataMigrationHub.php`
- **Group Navigasi**: `System` / `Migrasi Data`
- **Icon**: `heroicon-o-arrow-up-tray`
- **Label Navigasi**: `Migrasi Data Excel`
- **Hak Akses**:
  - `super_admin`: Dapat memilih gereja target dari dropdown.
  - `church_admin`: Terkunci otomatis pada gereja miliknya sendiri.
  - Role lainnya (`finance_admin`, `jemaat_admin`, `report_viewer`): Ditutup / 403 Forbidden.

### 5.2 Desain Tabulasi Modul
Halaman Filament dirancang dengan 4 tab interaktif:
1. **Tab 1: Jemaat & Keluarga**
   - Penjelasan ringkas data yang diimpor (Kartu Keluarga, NIK, Sakramen, Hubungan).
   - Tombol Aksi: `[Unduh Template Jemaat (.xlsx)]` (warna emerald/hijau Excel).
   - Card Upload: Form FileUpload menerima `.xlsx` (max 10MB).
   - Tombol Eksekusi: `[Proses Impor Jemaat]`.
2. **Tab 2: Master Keuangan (Kas & Kategori)**
   - Tombol Aksi: `[Unduh Template Keuangan (.xlsx)]`.
   - Card Upload & Tombol Eksekusi: `[Proses Impor Keuangan]`.
3. **Tab 3: Master Pelayan & Pejabat**
   - Tombol Aksi: `[Unduh Template Pelayan (.xlsx)]`.
   - Card Upload & Tombol Eksekusi: `[Proses Impor Pelayan]`.
4. **Tab 4: Master Acara & Jadwal Ibadah**
   - Tombol Aksi: `[Unduh Template Acara (.xlsx)]`.
   - Card Upload & Tombol Eksekusi: `[Proses Impor Acara]`.

### 5.3 Feedback Modal / Error Report Display
Ketika tombol import selesai dijalankan:
1. **Jika Sukses Penuh (100% Valid)**:
   - Tampilkan Filament Notification Banner warna hijau:
     *"Impor Selesai: {totalCreated} data baru ditambahkan, {totalUpdated} data diperbarui."*
2. **Jika Terdapat Baris Bermasalah**:
   - Tampilkan Notification Banner peringatan warna kuning/merah.
   - Buka Modal / Tampilkan Tabel Rincian Galat:
     - Kolom Tabel: `Nomor Baris`, `Kolom Bermasalah`, `Nilai yang Diinput`, `Keterangan Galat`.
     - Contoh pesan galat:
       - *"Baris 12: Kolom 'jenis_kelamin' berisi 'X' (Nilai yang diizinkan hanya L atau P)."*
       - *"Baris 25: Format tanggal lahir '1990/31/12' tidak valid (Gunakan format YYYY-MM-DD)."*
       - *"Baris 40: NIK '1801010101000001' sudah terdaftar pada jemaat lain."*
   - Transaksi database dijalankan per batch atau mencatat baris valid yang tetap masuk (*skip faulty rows with rollback safety*).

---

## 6. Rute Web & Kontrak API / Controller

### 6.1 Rute Tambahan di `routes/web.php`
```php
use App\Http\Controllers\DataMigrationController;

// Rute Publik CMS Landing Page Resmi
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rute Terproteksi Panel: Unduh Template & Eksekusi Import Excel
Route::middleware(['auth', 'verified'])->prefix('admin/migrasi-data')->group(function () {
    Route::get('/template/{module}', [DataMigrationController::class, 'downloadTemplate'])
        ->where('module', 'jemaat|keuangan|pelayan|acara')
        ->name('data-migration.template');

    Route::post('/import/{module}', [DataMigrationController::class, 'import'])
        ->where('module', 'jemaat|keuangan|pelayan|acara')
        ->name('data-migration.import');
});
```

---

## 7. Kebijakan Keamanan, RBAC & Multi-Tenant (Vera's Checklist)

1. **Strict Multi-Tenant Binding**:
   - Church Admin **DILARANG KERAS** menyisipkan record ke `church_id` lain. Variabel `$churchId` harus bersumber dari `$request->user()->church_id` dan tidak boleh dapat dioverride dari parameter query request.
   - Super Admin diberikan input eksplisit `church_id` dengan validasi `exists:churches,id`.
2. **Sanitasi File Upload**:
   - Ekstensi file wajib `.xlsx` atau `.xls`.
   - MIME type divalidasi ketat: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `application/vnd.ms-excel`.
   - Maksimum ukuran file: **10 MB**.
   - Temporary file langsung dihapus setelah pemrosesan selesai.
3. **Pencegahan Formula Injection (CSV/Excel Formula Injection)**:
   - Teks yang dimulai dengan karakter formula bahaya (`=`, `+`, `-`, `@`, `\t`, `\r`) pada cell dinetralisir agar tidak dievaluasi sebagai macro berbahaya saat dibuka kembali di spreadsheet lain.
4. **Audit Trail Logging**:
   - Setiap operasi import yang berhasil wajib mencatat log audit ke tabel `audit_logs` dengan aksi `DATA_MIGRATION_IMPORT`, mencatat jumlah record dibuat/diperbarui, nama modul, nama user, IP address, dan `church_id`.

---

## 8. User Stories & Kriteria Penerimaan (Acceptance Criteria / DoD)

### 8.1 Modul CMS Website Resmi Gereja
- **AC-CMS-01 (Landing Page Resmi)**: Mengakses `GET /` menampilkan halaman depan resmi gereja dengan nama "GKSBS Filadelfia", tagline, ayat tema tahunan, dan tanpa materi komersial/SaaS software.
- **AC-CMS-02 (Pintu Masuk Terpisah)**: Pada header halaman muka, terdapat tombol akses navigasi "Portal Jemaat" mengarah ke `/portal/login` dan "Area Majelis" mengarah ke `/admin/login`.
- **AC-CMS-03 (Sambutan Gembala & Profil)**: Halaman menampilkan sambutan gembala dengan foto, nama jabatan, profil singkat, visi, serta butir-butir misi yang dapat dibaca dengan nyaman di layar desktop dan mobile.
- **AC-CMS-04 (Kartu Pos Pelayanan / Cabang)**: Menampilkan kartu untuk tiap jemaat kelompok (Candimas, Trimulyo, Margomulyo) lengkap dengan alamat, kontak, dan tombol langsung menuju warta mingguan publik masing-masing (`/warta/{code}`).
- **AC-CMS-05 (CMS di Admin Panel)**: Super Admin dapat mengedit konten hero, tema, sambutan, visi-misi, dan kontak melalui Filament Admin Panel.
- **AC-CMS-06 (Auto Cache Invalidation)**: Setiap kali Super Admin menyimpan perubahan pada halaman pengaturan landing, cache `landing_setting_central` langsung dibersihkan dan halaman `/` langsung menampilkan data terbaru.

### 8.2 Modul Migrasi Data Excel Terpadu
- **AC-IMP-01 (Unduh Template Resmi)**: User berwenang dapat mengunduh 4 file template `.xlsx` resmi (Jemaat, Keuangan, Pelayan, Acara) yang memiliki Sheet Input dan Sheet Petunjuk/Kamus Nilai dengan styling header navy yang rapi.
- **AC-IMP-02 (Isolasi Tenant)**: Church Admin hanya dapat mengimpor data ke dalam gereja miliknya sendiri; data yang terbentuk di database otomatis memiliki `church_id` sesuai gereja aktor.
- **AC-IMP-03 (Pengelompokan Otomatis Keluarga)**: Baris jemaat dengan `no_kk` yang sama otomatis dikelompokkan ke dalam satu record `Family` tanpa membuat duplikasi keluarga.
- **AC-IMP-04 (Deduplikasi NIK)**: Impor data jemaat yang memiliki NIK sama dengan data yang sudah ada di gereja tsb akan memperbarui (*update*) data yang bersangkutan tanpa menduplikasi baris.
- **AC-IMP-05 (Pembuatan Sakramen Otomatis)**: Jika baris jemaat memuat status/tanggal baptis, sidi, atau nikah, record sakramen yang sesuai otomatis terbentuk di tabel `member_sacraments`.
- **AC-IMP-06 (Normalisasi Finansial)**: Impor master keuangan menormalisasi variasi teks ('Pemasukan', 'Masuk', 'Debit') menjadi `'debit'`, dan ('Pengeluaran', 'Keluar', 'Kredit') menjadi `'credit'`.
- **AC-IMP-07 (Resolusi Pejabat & Acara)**: Impor pejabat gereja berhasil mengaitkan `member_id` berdasarkan NIK untuk majelis lokal, dan menyimpan nama eksternal untuk pelayan tamu. Impor jadwal ibadah otomatis memetakan hari ke array JSON `days_of_week`.
- **AC-IMP-08 (Laporan Galat Terperinci)**: Jika terdapat baris yang salah (misal: format tanggal salah atau enum tidak valid), sistem menampilkan daftar nomor baris dan penyebab kesalahan secara terperinci.
- **AC-IMP-09 (Pencatatan Audit Trail)**: Proses impor terekam pada tabel `audit_logs` lengkap dengan `user_id`, `church_id`, dan deskripsi jumlah baris yang berhasil diproses.

---

## 9. Matriks Pembagian Tugas Tim Implementasi

### 9.1 Byte (Backend Engineer)
1. Buat file migrasi `2026_03_18_000001_create_landing_settings_table.php` dan model `LandingSetting`.
2. Buat Seeder default landing setting untuk GKSBS Filadelfia.
3. Buat kelas template export: `MemberFamilyTemplateExport`, `FinanceMasterTemplateExport`, `OfficialMinistryTemplateExport`, `EventScheduleTemplateExport`.
4. Buat kelas import logic: `MemberFamilyImport`, `FinanceMasterImport`, `OfficialMinistryImport`, `EventScheduleImport`.
5. Buat `DataMigrationController` dan endpoint unduh template serta pemrosesan file.
6. Buat Unit & Feature Tests untuk verifikasi import Excel, isolasi tenant, deduplikasi NIK, dan normalisasi debit/credit.

### 9.2 Pixel (Frontend Engineer)
1. Rombak total `resources/views/welcome.blade.php`:
   - Ganti materi SaaS lama menjadi Website Resmi Gereja Pusat/Induk.
   - Implementasikan Header dengan tombol terpisah "Portal Jemaat" dan "Area Majelis".
   - Tampilkan Hero Tema, Sambutan Gembala, Profil Visi-Misi, Kartu Cabang (Candimas, Trimulyo, Margomulyo), Jadwal Ibadah, dan Kontak.
2. Buat halaman CMS di Filament: `App\Filament\Clusters\System\Pages\ManageLandingPage.php` untuk pengelolaan konten landing page.
3. Buat halaman Filament `App\Filament\Pages\DataMigrationHub.php`:
   - 4 tab navigasi modul.
   - Komponen upload dropzone dan tombol unduh template.
   - Modal laporan feedback hasil import (tabel rincian error dan baris sukses).

### 9.3 Nova (QA Lead)
1. Uji skenario unduh template Excel dan periksa kelengkapan Sheet Formulir serta Sheet Petunjuk.
2. Uji impor dengan *dirty data* (format tanggal `DD/MM/YYYY`, spasi berlebih pada nama, enum huruf kecil/besar).
3. Uji pencegahan duplikasi NIK dan pembentukan relasi KK otomatis.
4. Uji isolasi data antar gereja (pastikan Church Admin Candimas tidak dapat mengimpor data ke Trimulyo).
5. Uji tampilan responsif halaman muka di resolusi mobile (360px), tablet (768px), dan desktop (1440px).

---
*Dokumen spesifikasi teknis ini bersifat mengikat dan menjadi acuan utama implementasi bagi Byte dan Pixel.*
