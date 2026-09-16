# SPESIFIKASI TEKNIS: LAPORAN KEUANGAN WARTA PER KANTONG (CASH FLOW TUNAI) & STANDARISASI KOP HEADER DOKUMEN GEREJA

- **Dokumen:** `analisis/SPEC-KOP-DAN-WARTA-KANTONG.md`
- **Versi:** v1.0 (Final Spec — Siap Implementasi)
- **Penulis:** Ada (Business Analyst / Technical Spec Writer)
- **Target Implementer:** Byte (Backend Engineer) & Pixel (Frontend Engineer)
- **Reviewer:** Nova (Lead QA), Vera (Security & Multi-Tenant), Ray (DevOps)
- **Base Branch:** master (`5d0daa0`)
- **Status:** APPROVED FOR IMPLEMENTATION

---

## 0. Konteks & Fakta Kode Terkini (Diverifikasi dari Master `5d0daa0`)

### 0.1 Modul Warta Jemaat Saat Ini
1. **Admin Warta** (`app/Filament/Clusters/Reporting/Pages/WartaJemaat.php` & `resources/views/filament/pages/warta-jemaat.blade.php`):
   - Menghasilkan ringkasan keuangan global: Saldo Awal, Total Pemasukan, Total Pengeluaran, Saldo Akhir.
   - Menampilkan tabel flat transaksi campuran tanpa pengelompokan per kas/dana (hanya filter kategori dan debit/credit).
2. **Publikasi Snapshot Warta** (`app/Http/Controllers/WartaPublishController.php` & `app/Models/WartaPublication.php`):
   - Menyimpan snapshot JSON ke kolom `warta_publications.content`.
   - Data keuangan yang disimpan saat ini hanya 4 angka ringkasan:
     ```php
     'finance' => [
         'opening_balance' => $data['openingBalance'] ?? 0,
         'total_income' => $data['totalIncome'] ?? 0,
         'total_expenses' => $data['totalExpenses'] ?? 0,
         'closing_balance' => $data['closingBalance'] ?? 0,
     ]
     ```
3. **Tampilan Publik & Portal Jemaat** (`resources/views/public/warta/show.blade.php` & `resources/views/portal/warta-detail.blade.php`):
   - Hanya menampilkan 4 kartu saldo global atau fallback ke format sederhana. Jemaat tidak dapat melihat transparansi persembahan masuk dan pengeluaran per kantong (pos kas).

### 0.2 Model Finansial Saat Ini
1. **Tabel `funds`**: Merepresentasikan pos dana/kas ("kantong"). Contoh: Kas Umum, Dana Pembangunan, Dana Diakonia.
2. **Tabel `financial_categories`**: Kategori transaksi dengan kolom `type` (`debit` = pemasukan, `credit` = pengeluaran). Contoh: Kolekte Ibadah, Persembahan Syukur, Listrik & Air, Bantuan Warga Sakit.
3. **Tabel `transactions`**: Memiliki relasi `fund_id`, `category_id`, `amount`, `type`, `transaction_date`, `church_id`.
4. **Prinsip Finansial Gereja**:
   - **100% Cash Flow Tunai**: Gereja mengelola uang secara tunai fisik per kantong/amplop/brankas.
   - **TIDAK ADA fitur perbankan**: Tidak ada rekening bank, nomor giro, transfer antar-bank, atau integrasi payment gateway. Seluruh arsitektur harus bebas dari atribut bank.

### 0.3 Identitas Gereja & Dokumen Ekspor Saat Ini
1. **Tabel `churches`**: Hanya memiliki kolom: `id`, `code`, `name`, `address`, `phone`, `timestamps`.
   - Belum memiliki kolom identitas resmi: `synod` (sinode/klasis/wilayah), `email`, `logo_path`, `website`.
2. **Pengaturan Gereja di Filament** (`ChurchResource.php`): Hanya bisa diakses oleh `super_admin`. `church_admin` belum memiliki akses untuk mengelola identitas dan logo gerejanya sendiri.
3. **Kop Dokumen Cetak/PDF Saat Ini**:
   - Belum ada komponen terpadu (`pdf.components.letterhead`).
   - Setiap view PDF (`pdf.report`, `pdf.akta-nikah`, `pdf.akta-lahir`, `pdf.dokumen-baptis-anak`, `pdf.dokumen-sidi`, `pdf.surat-kematian`) menduplikasi markup teks sederhana:
     ```html
     <div class="header">
         <h1>{{ $churchName ?? 'Portal Gereja' }}</h1>
         <div class="sub">{{ $title ?? '' }}</div>
     </div>
     ```
   - Belum mendukung logo gereja, sinode, kontak resmi, dan standarisasi garis kop resmi gereja.
   - **Catatan Teknis Dompdf** (`ReportExporter.php`): Konfigurasi `'isRemoteEnabled' => false` aktif. Gambar logo tidak dapat dipanggil menggunakan URL remote (`http://...`); wajib menggunakan jalur file lokal (`storage_path` / `public_path`) atau format Base64 Data URI agar tidak crash di Dompdf.

---

## 1. Problem Statement & User Stories

### 1.1 Problem Statement
1. **Ketidaktransparanan Laporan Finansial di Warta**: Laporan keuangan yang hanya menampilkan angka global (Saldo Awal, Total Masuk, Total Keluar, Saldo Akhir) tidak memenuhi ekspektasi transparansi jemaat gereja. Jemaat ingin mengetahui dengan jelas:
   - Berapa kolekte ibadah minggu, perpuluhan, dan syukur yang masuk ke **Kas Umum (Kantong 1)**.
   - Berapa dana yang terkumpul di **Dana Pembangunan (Kantong 2)** dan **Dana Diakonia (Kantong 3)**.
   - Pos apa saja yang dikeluarkan (biaya operasional, listrik/air, santunan warga sakit).
2. **Ketiadaan Standar Kop Dokumen Resmi**: Dokumen formal yang diterbitkan gereja (Surat Baptis, Sidi, Akta Nikah, Surat Kematian, serta Laporan Resmi 7 Modul) belum memiliki kop surat resmi gereja berlogo dan beridentitas sinode/kontak lengkap, sehingga terkesan tidak formal saat dicetak.

### 1.2 User Stories
1. **Sebagai Jemaat Gereja**, saya ingin melihat rincian pemasukan dan pengeluaran per kantong di Warta Jemaat (baik di portal mandiri maupun halaman publik), sehingga saya yakin dan memahami bagaimana persembahan dikelola.
2. **Sebagai Majelis / Church Admin**, saya ingin sistem warta secara otomatis mengelompokkan transaksi kas tunai per kantong (Fund) lengkap dengan saldo awal, rincian pos masuk/keluar, dan saldo akhir setiap minggu tanpa perlu menghitung ulang manual.
3. **Sebagai Sekretariat / Majelis Gereja**, saya ingin dapat mengunggah logo gereja, mengisi nama Sinode/Klasis, alamat lengkap, telepon, dan email resmi gereja melalui panel admin, sehingga seluruh dokumen cetak dan PDF yang di-export memiliki Kop Header resmi yang konsisten dan rapi.
4. **Sebagai Bendahara Gereja**, saya membutuhkan laporan yang murni mencerminkan transaksi tunai (cash flow) tanpa kerumitan modul rekening bank yang tidak digunakan oleh jemaat lokal kami.

---

## 2. Spesifikasi Database & Model Identitas Gereja

### 2.1 Migrasi Tabel `churches`
Buat file migrasi baru:
`database/migrations/2026_03_17_000001_add_identity_columns_to_churches_table.php`

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
        Schema::table('churches', function (Blueprint $table) {
            $table->string('synod')->nullable()->after('name')
                ->comment('Nama Sinode / Klasis / Wilayah / Lembaga Gereja Induk');
            $table->string('email')->nullable()->after('phone')
                ->comment('Email resmi kantor / sekretariat gereja');
            $table->string('logo_path')->nullable()->after('email')
                ->comment('Path file logo di storage disk public (churches/logos/...)');
            $table->string('website')->nullable()->after('logo_path')
                ->comment('Website resmi gereja (opsional)');
        });
    }

    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['synod', 'email', 'logo_path', 'website']);
        });
    }
};
```

> **Aturan Finansial Ketat:**
> - DILARANG menambahkan kolom nomor rekening, nama bank, atau atas nama rekening pada tabel `churches` maupun tabel transaksi. Transaksi gereja 100% tunai kas fisik.

### 2.2 Model `App\Models\Church`
Perbarui `app/Models/Church.php`:
1. Tambahkan atribut baru ke `$fillable`:
   ```php
   protected $fillable = [
       'code',
       'name',
       'synod',
       'address',
       'phone',
       'email',
       'logo_path',
       'website',
   ];
   ```
2. Tambahkan accessor pendukung logo yang tahan banting untuk Dompdf dan Web:
   ```php
   /**
    * URL logo untuk ditampilkan pada halaman web / portal.
    */
   public function getLogoUrlAttribute(): ?string
   {
       if (! $this->logo_path) {
           return null;
       }

       return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
   }

   /**
    * Path fisik absolut logo untuk dibaca Dompdf secara lokal.
    */
   public function getLogoRealPathAttribute(): ?string
   {
       if (! $this->logo_path) {
           return null;
       }

       $path = \Illuminate\Support\Facades\Storage::disk('public')->path($this->logo_path);

       return file_exists($path) ? $path : null;
   }

   /**
    * Data URI Base64 logo untuk Dompdf (paling aman dari batasan path & isRemoteEnabled).
    */
   public function getLogoBase64Attribute(): ?string
   {
       $realPath = $this->logo_real_path;
       if (! $realPath) {
           return null;
       }

       try {
           $mime = mime_content_type($realPath) ?: 'image/png';
           $data = base64_encode(file_get_contents($realPath));

           return "data:{$mime};base64,{$data}";
       } catch (\Throwable) {
           return null;
       }
   }
   ```

### 2.3 Form Pengaturan Identitas Gereja (Filament)
Perbarui form di `app/Filament/Clusters/System/Resources/Church/ChurchResource.php`:
1. **Fieldset / Section Identitas Resmi**:
   - `FileUpload::make('logo_path')`
     - Label: `Logo Gereja`
     - Disk: `public`
     - Directory: `church-logos`
     - Visibility: `public`
     - Image, Max Size: `2048` KB (2MB)
     - AcceptedFileTypes: `['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml']`
     - Helper text: `Format PNG/JPG/SVG transparan. Digunakan untuk Kop Surat Resmi dokumen cetak dan PDF.`
   - `TextInput::make('name')` -> Label: `Nama Jemaat / Gereja` (wajib, max: 255)
   - `TextInput::make('synod')` -> Label: `Sinode / Klasis / Wilayah` (opsional, contoh: `Sinode GKSBS / Klasis Tulang Bawang`, max: 255)
   - `TextInput::make('code')` -> Label: `Kode Gereja` (unik, disabled/read-only jika bukan super_admin)
2. **Fieldset / Section Kontak & Kesekretariatan**:
   - `Textarea::make('address')` -> Label: `Alamat Lengkap Gereja` (wajib diisi untuk kop resmi)
   - `TextInput::make('phone')` -> Label: `No. Telepon Sekretariat` (tel)
   - `TextInput::make('email')` -> Label: `Email Resmi Gereja` (email)
   - `TextInput::make('website')` -> Label: `Website Gereja` (url, opsional)
3. **Otorisasi & Akses Role**:
   - `super_admin`: Dapat melihat dan mengedit semua gereja.
   - `church_admin`: Diizinkan membuka dan mengedit data gerejanya sendiri (`$record->id === auth()->user()->church_id`). Tidak boleh mengubah `code`.
   - Update method policy / permission di `ChurchResource::canUpdate()` dan `canViewAny()`:
     ```php
     public static function canViewAny(): bool
     {
         return in_array(auth()->user()?->role, ['super_admin', 'church_admin'], true);
     }

     public static function canUpdate(Model $record): bool
     {
         $user = auth()->user();
         if (! $user) return false;
         if ($user->role === 'super_admin') return true;
         return $user->role === 'church_admin' && (int) $user->church_id === (int) $record->id;
     }
     ```

---

## 3. Desain Komponen Kop Surat & Standarisasi PDF

### 3.1 Komponen Blade PDF: `resources/views/pdf/components/letterhead.blade.php`
Komponen ini menjadi **satu-satunya sumber kebenaran** kop surat untuk seluruh dokumen cetak Dompdf di portal gereja.

#### Struktur File:
`resources/views/pdf/components/letterhead.blade.php`

```blade
@props([
    'church' => null,
    'churchName' => null,
    'synod' => null,
    'churchAddress' => null,
    'churchPhone' => null,
    'churchEmail' => null,
    'logoBase64' => null,
    'title' => null,
    'subTitle' => null,
    'periodLabel' => null,
    'documentNumber' => null,
])

@php
    // Fallback resolver jika objek $church dilewatkan
    $name = $churchName ?? $church?->name ?? 'PORTAL GEREJA';
    $synodText = $synod ?? $church?->synod ?? null;
    $address = $churchAddress ?? $church?->address ?? null;
    $phone = $churchPhone ?? $church?->phone ?? null;
    $email = $churchEmail ?? $church?->email ?? null;
    $logo = $logoBase64 ?? $church?->logo_base64 ?? null;
@endphp

<div class="letterhead-container" style="width: 100%; margin-bottom: 18px; font-family: 'DejaVu Sans', sans-serif;">
    <table style="width: 100%; border-collapse: collapse; border: none;">
        <tr>
            @if ($logo)
                <td style="width: 75px; vertical-align: middle; text-align: center; padding-right: 12px; border: none;">
                    <img src="{{ $logo }}" alt="Logo" style="max-width: 70px; max-height: 70px; object-fit: contain;">
                </td>
            @endif
            <td style="vertical-align: middle; text-align: center; border: none;">
                @if ($synodText)
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #4b5563; margin-bottom: 2px;">
                        {{ $synodText }}
                    </div>
                @endif
                <div style="font-size: 17px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #111827; line-height: 1.2;">
                    {{ $name }}
                </div>
                @if ($address)
                    <div style="font-size: 9.5px; color: #374151; margin-top: 3px; line-height: 1.3;">
                        {{ $address }}
                    </div>
                @endif
                @if ($phone || $email)
                    <div style="font-size: 8.5px; color: #6b7280; margin-top: 2px;">
                        @if ($phone) Telp: {{ $phone }} @endif
                        @if ($phone && $email) &bull; @endif
                        @if ($email) Email: {{ $email }} @endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Garis Pemisah Kop Surat Resmi: Garis Ganda Standar (Tebal 2px + Tipis 1px) --}}
    <div style="border-bottom: 2px solid #111827; margin-top: 8px;"></div>
    <div style="border-bottom: 1px solid #111827; margin-top: 2px;"></div>

    {{-- Judul Dokumen (Jika disediakan) --}}
    @if ($title)
        <div style="text-align: center; margin-top: 14px; margin-bottom: 4px;">
            <div style="font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #111827;">
                {{ $title }}
            </div>
            @if ($documentNumber)
                <div style="font-size: 10px; color: #4b5563; margin-top: 2px;">
                    Nomor: {{ $documentNumber }}
                </div>
            @endif
            @if ($periodLabel)
                <div style="font-size: 10px; font-weight: 600; color: #4b5563; margin-top: 2px;">
                    Periode: {{ $periodLabel }}
                </div>
            @endif
            @if ($subTitle)
                <div style="font-size: 9.5px; color: #6b7280; margin-top: 1px;">
                    {{ $subTitle }}
                </div>
            @endif
        </div>
    @endif
</div>
```

### 3.2 Penanganan Khusus Dompdf (Tanpa Crash)
1. **Mengapa Base64 Data URI?**
   - `ReportExporter.php` mengonfigurasi `'isRemoteEnabled' => false`. Jika menggunakan tag `<img src="http://...">`, Dompdf akan menolak me-render gambar atau melempar exception `Remote file access denied`.
   - Menggunakan Base64 Data URI (`data:image/png;base64,...`) menyelesaikan 3 masalah sekaligus:
     * Tidak membutuhkan koneksi HTTP eksternal.
     * Tidak terpengaruh pembatasan folder `chroot` lokal Dompdf di berbagai OS/server.
     * Dapat langsung diselipkan ke dalam file HTML template PDF.
2. **Fallback Graceful**:
   - Jika gereja belum mengunggah logo (`logo_path` null), tabel kop secara otomatis meniadakan kolom logo dan memposisikan teks identitas gereja di tengah (*center-aligned*) secara proporsional.

### 3.3 Integrasi ke Dokumen PDF Eksisting
Seluruh file Blade PDF berikut **wajib** diganti bagian header manualnya dengan pemanggilan `@include('pdf.components.letterhead', ...)`:
1. `resources/views/pdf/report.blade.php` (Digunakan oleh seluruh 7 modul laporan di `BaseReportPage::downloadPdf()`).
2. `resources/views/pdf/akta-nikah.blade.php`
3. `resources/views/pdf/akta-lahir.blade.php`
4. `resources/views/pdf/dokumen-baptis-anak.blade.php`
5. `resources/views/pdf/dokumen-sidi.blade.php`
6. `resources/views/pdf/surat-kematian.blade.php`

**Contoh Refactoring di `resources/views/pdf/report.blade.php`**:
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; line-height: 1.4; }
        h2 { font-size: 12px; margin: 16px 0 6px; color: #111827; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; font-size: 9.5px; }
        th { background: #f3f4f6; font-weight: 600; }
        .footer { margin-top: 24px; font-size: 8.5px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    @include('pdf.components.letterhead', [
        'church' => $church ?? null,
        'churchName' => $churchName ?? null,
        'title' => $title ?? null,
        'periodLabel' => $periodLabel ?? null,
    ])

    @forelse ($blocks ?? [] as $block)
        ...
    @empty
        <p>Tidak ada data.</p>
    @endforelse

    <div class="footer">Diterbitkan oleh Portal Gereja • Laporan Resmi</div>
</body>
</html>
```

---

## 4. Logika Keuangan Warta per Kantong (Cash Flow Tunai)

### 4.1 Definisi "Kantong" (Fund)
Dalam tata kelola kas gereja, uang persembahan dipisahkan berdasarkan fungsi pelayanan:
1. **Kantong 1: Kas Umum / Operasional Jemaat**:
   - *Pemasukan*: Kolekte Ibadah Minggu, Persembahan Syukur, Persembahan Persepuluhan, Persembahan Baptis/Sidi.
   - *Pengeluaran*: Operasional Listrik/Air/Internet, ATK & Fotokopi Warta, Honorarium Pelayan Firman & Pemusik, Konsumsi Rapat Majelis.
2. **Kantong 2: Dana Pembangunan**:
   - *Pemasukan*: Kotak Khusus Pembangunan, Janji Iman Pembangunan, Sumbangan Material Tunai.
   - *Pengeluaran*: Pembelian Semen/Cat/Keramik, Upah Tukang Pembangunan.
3. **Kantong 3: Dana Diakonia / Kasih**:
   - *Pemasukan*: Kolekte Khusus Diakonia, Sumbangan Kasih Bencana.
   - *Pengeluaran*: Bantuan Pengobatan Warga Sakit, Santunan Duka, Bantuan Pendidikan Anak Jemaat Kurang Mampu.
4. **Kantong Khusus Lainnya** (Dinamis sesuai master data `funds` pada masing-masing gereja).

### 4.2 Kalkulasi Data di `WartaJemaat::getReportData()`
Perbarui kalkulasi di `app/Filament/Clusters/Reporting/Pages/WartaJemaat.php`:

```php
// Ambil semua Fund (Kantong) milik gereja aktif
$funds = $this->scopeToActiveChurch(Fund::query())->orderBy('name')->get();

// Optimasi Single-Query Saldo Awal per Fund sebelum tanggal mulai warta
$openingBalances = Transaction::query()
    ->whereIn('fund_id', $funds->pluck('id'))
    ->whereDate('transaction_date', '<', $startDate)
    ->selectRaw('fund_id, type, SUM(amount) as total')
    ->groupBy('fund_id', 'type')
    ->get()
    ->groupBy('fund_id')
    ->map(function ($items) {
        $debit = (int) ($items->firstWhere('type', 'debit')?->total ?? 0);
        $credit = (int) ($items->firstWhere('type', 'credit')?->total ?? 0);
        return $debit - $credit;
    });

// Ambil transaksi periode warta dengan eager loading category dan fund
$periodTransactions = $this->scopeToActiveChurch(Transaction::with(['fund', 'category']))
    ->whereBetween('transaction_date', [$startDate, $endDate])
    ->orderBy('transaction_date')
    ->get();

$fundBreakdowns = [];
$consolidatedOpening = 0;
$consolidatedIncome = 0;
$consolidatedExpenses = 0;

foreach ($funds as $fund) {
    $fundTxns = $periodTransactions->where('fund_id', $fund->id);

    $opening = (int) ($openingBalances[$fund->id] ?? 0);

    // Grouping Pemasukan (debit) per kategori
    $incomeTxns = $fundTxns->where('type', 'debit');
    $incomeItems = $incomeTxns->groupBy(fn ($t) => $t->category?->name ?? 'Pemasukan Lain-lain')
        ->map(fn ($group, $catName) => [
            'category' => $catName,
            'amount' => (int) $group->sum('amount'),
        ])->values()->all();
    $fundTotalIncome = (int) $incomeTxns->sum('amount');

    // Grouping Pengeluaran (credit) per kategori
    $expenseTxns = $fundTxns->where('type', 'credit');
    $expenseItems = $expenseTxns->groupBy(fn ($t) => $t->category?->name ?? 'Pengeluaran Lain-lain')
        ->map(fn ($group, $catName) => [
            'category' => $catName,
            'amount' => (int) $group->sum('amount'),
        ])->values()->all();
    $fundTotalExpense = (int) $expenseTxns->sum('amount');

    $closing = $opening + $fundTotalIncome - $fundTotalExpense;

    $fundBreakdowns[] = [
        'id' => $fund->id,
        'name' => $fund->name,
        'opening_balance' => $opening,
        'income' => [
            'total' => $fundTotalIncome,
            'items' => $incomeItems,
        ],
        'expense' => [
            'total' => $fundTotalExpense,
            'items' => $expenseItems,
        ],
        'closing_balance' => $closing,
    ];

    $consolidatedOpening += $opening;
    $consolidatedIncome += $fundTotalIncome;
    $consolidatedExpenses += $fundTotalExpense;
}

$consolidatedClosing = $consolidatedOpening + $consolidatedIncome - $consolidatedExpenses;
```

**Backward Compatibility Guard:**
Return array dari `getReportData()` **harus tetap menyertakan** key legacy (`openingBalance`, `totalIncome`, `totalExpenses`, `closingBalance`) sehingga tidak mematahkan unit/feature test yang sudah ada:
```php
return [
    'events' => $events,
    'birthdays' => $birthdays,
    'transactions' => $transactions,
    'sacraments' => $sacraments,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'church' => $this->activeChurchModel(), // Model Church untuk kop
    'churchName' => $this->activeChurchName(),
    'churchAddress' => $this->getChurchAddress(),
    'periodLabel' => $this->formatPeriodLabel($startDate, $endDate),
    'editionLabel' => $this->formatEditionLabel($startDate),

    // Data Legacy (Konsolidasi Global)
    'openingBalance' => $consolidatedOpening,
    'totalIncome' => $consolidatedIncome,
    'totalExpenses' => $consolidatedExpenses,
    'closingBalance' => $consolidatedClosing,

    // Data Baru: Rincian per Kantong Kas Tunai
    'fundBreakdowns' => $fundBreakdowns,
];
```

---

## 5. Struktur Snapshot JSON Data Warta (`warta_publications.content`)

Saat admin mempublikasikan warta melalui `WartaPublishController`, method `snapshot()` mengubah array data internal menjadi snapshot JSON publik.

### 5.1 Skema JSON Payload (`warta_publications.content`)
```json
{
  "church": {
    "name": "GKSBS Klasis Tulang Bawang",
    "synod": "Sinode GKSBS",
    "address": "Jl. Lintas Timur No. 45, Tulang Bawang, Lampung",
    "phone": "(0726) 21045",
    "email": "sekretariat@gksbs-tb.or.id",
    "logo_url": "/storage/church-logos/gksbs.png"
  },
  "period_label": "14 – 20 September 2026",
  "edition_label": "Edisi Minggu ke-38 — 2026",
  "events": [
    {
      "name": "Kebaktian Umum 1 (Pagi)",
      "start": "20/09/2026 07:00",
      "location": "Gedung Utama",
      "officials": "Pdt. Markus (Khotbah), Dkn. Sarah (Liturgis)"
    }
  ],
  "birthdays": [
    {
      "name": "Yohanes Prasetyo",
      "date": "16/09"
    }
  ],
  "sacraments": [
    {
      "date": "20/09/2026",
      "type": "baptis_anak",
      "name": "Immanuel Timothy",
      "official": "Pdt. Markus"
    }
  ],
  "finance": {
    "opening_balance": 14500000,
    "total_income": 6500000,
    "total_expenses": 2800000,
    "closing_balance": 18200000,
    "funds": [
      {
        "id": 1,
        "name": "Kas Umum / Operasional (Kantong 1)",
        "opening_balance": 8500000,
        "income": {
          "total": 4200000,
          "items": [
            { "category": "Kolekte Ibadah Minggu", "amount": 2500000 },
            { "category": "Persepuluhan", "amount": 1200000 },
            { "category": "Persembahan Syukur", "amount": 500000 }
          ]
        },
        "expense": {
          "total": 2100000,
          "items": [
            { "category": "Listrik & Internet Gereja", "amount": 800000 },
            { "category": "ATK & Fotokopi Warta", "amount": 300000 },
            { "category": "Transport Pelayan Tamu", "amount": 1000000 }
          ]
        },
        "closing_balance": 10600000
      },
      {
        "id": 2,
        "name": "Dana Pembangunan (Kantong 2)",
        "opening_balance": 4000000,
        "income": {
          "total": 1800000,
          "items": [
            { "category": "Kotak Pembangunan", "amount": 1000000 },
            { "category": "Janji Iman Jemaat", "amount": 800000 }
          ]
        },
        "expense": {
          "total": 500000,
          "items": [
            { "category": "Pembelian Semen & Pasir", "amount": 500000 }
          ]
        },
        "closing_balance": 5300000
      },
      {
        "id": 3,
        "name": "Dana Diakonia Kasih (Kantong 3)",
        "opening_balance": 2000000,
        "income": {
          "total": 500000,
          "items": [
            { "category": "Kolekte Khusus Diakonia", "amount": 500000 }
          ]
        },
        "expense": {
          "total": 200000,
          "items": [
            { "category": "Bantuan Pengobatan Jemaat", "amount": 200000 }
          ]
        },
        "closing_balance": 2300000
      }
    ]
  }
}
```

### 5.2 Implementasi di `WartaPublishController::snapshot()`
Perbarui `snapshot()` di `app/Http/Controllers/WartaPublishController.php`:
```php
private function snapshot(array $data, ?Church $church = null): array
{
    // Snapshot Event, Birthday, Sacrament tetap sama...

    return [
        'church' => [
            'name' => $church?->name ?? $data['churchName'] ?? 'Gereja',
            'synod' => $church?->synod,
            'address' => $church?->address ?? $data['churchAddress'] ?? '',
            'phone' => $church?->phone,
            'email' => $church?->email,
            'logo_url' => $church?->logo_url,
        ],
        'church_name' => $church?->name ?? $data['churchName'] ?? 'Gereja',
        'church_address' => $church?->address ?? $data['churchAddress'] ?? '',
        'period_label' => $data['periodLabel'] ?? null,
        'edition_label' => $data['editionLabel'] ?? null,
        'events' => $events,
        'birthdays' => $birthdays,
        'sacraments' => $sacraments,
        'finance' => [
            'opening_balance' => (int) ($data['openingBalance'] ?? 0),
            'total_income' => (int) ($data['totalIncome'] ?? 0),
            'total_expenses' => (int) ($data['totalExpenses'] ?? 0),
            'closing_balance' => (int) ($data['closingBalance'] ?? 0),
            'funds' => $data['fundBreakdowns'] ?? [],
        ],
    ];
}
```

---

## 6. Wireframe & Tata Letak UI Blade Warta

Laporan keuangan warta per kantong disajikan pada 3 halaman:
1. **Admin Filament**: `resources/views/filament/pages/warta-jemaat.blade.php`
2. **Warta Publik**: `resources/views/public/warta/show.blade.php`
3. **Portal Mandiri Jemaat**: `resources/views/portal/warta-detail.blade.php`

### 6.1 Wireframe Tata Letak (ASCII)

```text
+-----------------------------------------------------------------------------------+
| [LOGO]   SINODE GEREJA KRISTEN SUMATERA BAGIAN SELATAN (GKSBS)                    |
|          GKSBS JEMAAT TULANG BAWANG                                               |
|          Jl. Lintas Timur No. 45, Tulang Bawang | Telp: (0726) 21045              |
|===================================================================================|
|                            WARTA JEMAAT MINGGUAN                                  |
|                 Edisi Minggu ke-38 — Periode: 14 – 20 September 2026              |
+-----------------------------------------------------------------------------------+
| ... Jadwal Ibadah, Roster Pelayan, Ulang Tahun, Sakramen ...                      |
+-----------------------------------------------------------------------------------+
| 6. LAPORAN KEUANGAN KAS TUNAI PER KANTONG                                         |
| Catatan: Seluruh persembahan dan pengeluaran dikelola 100% secara tunai fisik.   |
|                                                                                   |
| [KONSOLIDASI KAS GEREJA]                                                          |
| +-------------------+ +-------------------+ +-------------------+ +---------------+ |
| | Saldo Awal Kas    | | Total Uang Masuk  | | Total Uang Keluar | | Saldo Akhir   | |
| | Rp 14.500.000     | | (+) Rp 6.500.000  | | (-) Rp 2.800.000  | | Rp 18.200.000 | |
| +-------------------+ +-------------------+ +-------------------+ +---------------+ |
|                                                                                   |
| ── KANTONG 1: KAS UMUM & OPERASIONAL ───────────────────────────────────────────  |
| Saldo Awal: Rp 8.500.000                                                          |
|                                                                                   |
|   UANG MASUK (PEMASUKAN)               |   UANG KELUAR (PENGELUARAN)              |
|   - Kolekte Ibadah Minggu: Rp 2.500.000|   - Listrik & Internet    : Rp 800.000   |
|   - Perpuluhan Jemaat    : Rp 1.200.000|   - ATK & Fotokopi Warta  : Rp 300.000   |
|   - Persembahan Syukur   : Rp   500.000|   - Transport Pelayan Tamu: Rp 1.000.000 |
|   -------------------------------------|   -------------------------------------  |
|   Subtotal Masuk: (+) Rp 4.200.000     |   Subtotal Keluar: (-) Rp 2.100.000      |
|                                                                                   |
|   >>> Saldo Akhir Kas Umum: Rp 10.600.000                                         |
|                                                                                   |
| ── KANTONG 2: DANA PEMBANGUNAN GEDUNG ──────────────────────────────────────────   |
| Saldo Awal: Rp 4.000.000                                                          |
|                                                                                   |
|   UANG MASUK (PEMASUKAN)               |   UANG KELUAR (PENGELUARAN)              |
|   - Kotak Pembangunan    : Rp 1.000.000|   - Pembelian Semen&Pasir : Rp 500.000   |
|   - Janji Iman Jemaat    : Rp   800.000|                                          |
|   -------------------------------------|   -------------------------------------  |
|   Subtotal Masuk: (+) Rp 1.800.000     |   Subtotal Keluar: (-) Rp 500.000        |
|                                                                                   |
|   >>> Saldo Akhir Pembangunan: Rp 5.300.000                                       |
|                                                                                   |
| ── KANTONG 3: DANA DIAKONIA & SOSIAL ───────────────────────────────────────────  |
| Saldo Awal: Rp 2.000.000                                                          |
|                                                                                   |
|   UANG MASUK (PEMASUKAN)               |   UANG KELUAR (PENGELUARAN)              |
|   - Kolekte Diakonia     : Rp   500.000|   - Bantuan Warga Sakit   : Rp 200.000   |
|   -------------------------------------|   -------------------------------------  |
|   Subtotal Masuk: (+) Rp   500.000     |   Subtotal Keluar: (-) Rp 200.000        |
|                                                                                   |
|   >>> Saldo Akhir Diakonia: Rp 2.300.000                                          |
+-----------------------------------------------------------------------------------+
|                                 Tanda Tangan                                      |
|            Ketua Majelis Jemaat                    Bendahara Jemaat               |
|            ( .................... )                ( .................... )       |
+-----------------------------------------------------------------------------------+
```

### 6.2 Contoh Implementasi Blade Komponen Kantong
Gunakan markup seragam (Tailwind CSS) yang mendukung mode cetak (`print:bg-white`, `print:border-gray-400`):

```blade
{{-- 6. Laporan Keuangan per Kantong --}}
<div class="px-8 pt-8 print:pt-6">
    <div class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Laporan Keuangan Kas Tunai per Kantong</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Arus kas tunai masuk dan keluar per pos dana pelayanan</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
            100% Kas Tunai
        </span>
    </div>

    {{-- Ringkasan Konsolidasi --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3 text-center print:bg-white print:border-gray-300">
            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Total Saldo Awal</p>
            <p class="mt-0.5 text-base font-bold text-gray-900 dark:text-white">Rp{{ number_format($reportData['openingBalance'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-900/20 p-3 text-center print:bg-white print:border-gray-300">
            <p class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">Total Uang Masuk</p>
            <p class="mt-0.5 text-base font-black text-emerald-700 dark:text-emerald-400">+Rp{{ number_format($reportData['totalIncome'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50/60 dark:bg-red-900/20 p-3 text-center print:bg-white print:border-gray-300">
            <p class="text-[11px] font-semibold text-red-700 dark:text-red-400">Total Uang Keluar</p>
            <p class="mt-0.5 text-base font-black text-red-700 dark:text-red-400">−Rp{{ number_format($reportData['totalExpenses'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/60 dark:bg-amber-900/20 p-3 text-center print:bg-white print:border-gray-300">
            <p class="text-[11px] font-semibold text-amber-700 dark:text-amber-400">Total Saldo Akhir</p>
            <p class="mt-0.5 text-base font-black text-amber-700 dark:text-amber-400">Rp{{ number_format($reportData['closingBalance'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Detail Setiap Kantong (Fund) --}}
    <div class="space-y-6">
        @forelse ($reportData['fundBreakdowns'] as $fund)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/40 p-4 shadow-sm print:border-gray-300 print:shadow-none">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-700/60 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-amber-500"></div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ $fund['name'] }}</h3>
                    </div>
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        Saldo Awal: <span class="text-gray-900 dark:text-gray-100 font-bold">Rp{{ number_format($fund['opening_balance'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    {{-- Uang Masuk --}}
                    <div class="rounded-lg bg-emerald-50/40 dark:bg-emerald-950/20 p-3 border border-emerald-100 dark:border-emerald-900/40">
                        <div class="flex items-center justify-between font-semibold text-xs text-emerald-800 dark:text-emerald-300 mb-2 border-b border-emerald-200/50 pb-1">
                            <span>Pos Uang Masuk</span>
                            <span>Jumlah</span>
                        </div>
                        <ul class="space-y-1.5 text-xs">
                            @forelse ($fund['income']['items'] as $item)
                                <li class="flex justify-between text-gray-700 dark:text-gray-300">
                                    <span>{{ $item['category'] }}</span>
                                    <span class="font-semibold text-emerald-700 dark:text-emerald-400">+Rp{{ number_format($item['amount'], 0, ',', '.') }}</span>
                                </li>
                            @empty
                                <li class="text-gray-400 italic text-center py-1">Tidak ada uang masuk</li>
                            @endforelse
                        </ul>
                        <div class="mt-3 pt-2 border-t border-emerald-200/60 flex justify-between font-bold text-xs text-emerald-800 dark:text-emerald-300">
                            <span>Total Masuk</span>
                            <span>+Rp{{ number_format($fund['income']['total'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Uang Keluar --}}
                    <div class="rounded-lg bg-red-50/40 dark:bg-red-950/20 p-3 border border-red-100 dark:border-red-900/40">
                        <div class="flex items-center justify-between font-semibold text-xs text-red-800 dark:text-red-300 mb-2 border-b border-red-200/50 pb-1">
                            <span>Pos Uang Keluar</span>
                            <span>Jumlah</span>
                        </div>
                        <ul class="space-y-1.5 text-xs">
                            @forelse ($fund['expense']['items'] as $item)
                                <li class="flex justify-between text-gray-700 dark:text-gray-300">
                                    <span>{{ $item['category'] }}</span>
                                    <span class="font-semibold text-red-700 dark:text-red-400">−Rp{{ number_format($item['amount'], 0, ',', '.') }}</span>
                                </li>
                            @empty
                                <li class="text-gray-400 italic text-center py-1">Tidak ada uang keluar</li>
                            @endforelse
                        </ul>
                        <div class="mt-3 pt-2 border-t border-red-200/60 flex justify-between font-bold text-xs text-red-800 dark:text-red-300">
                            <span>Total Keluar</span>
                            <span>−Rp{{ number_format($fund['expense']['total'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Footer Saldo Akhir Kantong --}}
                <div class="mt-3 pt-2.5 border-t border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50/60 dark:bg-gray-800/80 rounded-lg px-3 py-2">
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Saldo Akhir {{ $fund['name'] }}</span>
                    <span class="text-sm font-black text-amber-700 dark:text-amber-400">
                        Rp{{ number_format($fund['closing_balance'], 0, ',', '.') }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-6 text-center text-sm text-gray-500">
                Belum ada kas/kantong yang tercatat pada periode ini.
            </div>
        @endforelse
    </div>
</div>
```

---

## 7. Pembagian Tugas & Rencana Implementasi

### 7.1 Tugas Byte (Backend Engineer)
- [ ] **B1**: Buat migrasi `add_identity_columns_to_churches_table` (`synod`, `email`, `logo_path`, `website`).
- [ ] **B2**: Perbarui model `App\Models\Church` (fillable, accessors: `logo_url`, `logo_real_path`, `logo_base64`).
- [ ] **B3**: Perbarui `ChurchFactory` dengan default state kolom baru.
- [ ] **B4**: Perbarui otorisasi & form di `ChurchResource` (akses church_admin untuk gerejanya sendiri + FileUpload logo).
- [ ] **B5**: Refactor `WartaJemaat::getReportData()` untuk menghasilkan agregasi `fundBreakdowns` dengan single-query saldo awal.
- [ ] **B6**: Perbarui `WartaJemaat::exportBlocks()` agar ekspor Excel dan PDF memuat tabel per kantong.
- [ ] **B7**: Perbarui `WartaPublishController::snapshot()` untuk menyimpan objek `church` dan `finance.funds`.
- [ ] **B8**: Perbarui `BaseReportPage::downloadPdf()` agar mengoper model `Church` ke view PDF.
- [ ] **B9**: Buat Unit/Feature Tests untuk memverifikasi kalkulasi per kantong, publikasi snapshot warta, dan ekspor PDF.

### 7.2 Tugas Pixel (Frontend Engineer)
- [ ] **P1**: Buat Blade component `resources/views/pdf/components/letterhead.blade.php`.
- [ ] **P2**: Refactor seluruh template PDF (`pdf.report`, `pdf.akta-nikah`, `pdf.akta-lahir`, `pdf.dokumen-baptis-anak`, `pdf.dokumen-sidi`, `pdf.surat-kematian`) menggunakan komponen letterhead resmi.
- [ ] **P3**: Implementasikan UI Keuangan per Kantong di Warta Admin Filament (`filament.pages.warta-jemaat.blade.php`).
- [ ] **P4**: Implementasikan UI Keuangan per Kantong di Warta Publik (`public.warta.show.blade.php`).
- [ ] **P5**: Implementasikan UI Keuangan per Kantong di Portal Mandiri Jemaat (`portal.warta-detail.blade.php`).
- [ ] **P6**: Pastikan tampilan cetak (`@media print`) pada Warta Admin dan Publik rapi, tanpa background pekat, dan hemat tinta kertas A4.

---

## 8. Kriteria Penerimaan (Acceptance Criteria / DoD)

### Sub-Task A: Migrasi & Model Identitas Gereja (Byte)
- **AC-KOP-01**: Migrasi berhasil dieksekusi tanpa error di SQLite, MySQL, dan PostgreSQL. Kolom `synod`, `email`, `logo_path`, `website` bertipe `string` nullable.
- **AC-KOP-02**: Model `Church` memiliki atribut `$fillable` lengkap untuk keempat kolom baru tersebut.
- **AC-KOP-03**: Accessor `$church->logo_url` mengembalikan URL publik storage jika logo diisi, atau `null` jika kosong.
- **AC-KOP-04**: Accessor `$church->logo_base64` mengembalikan string berformat `data:image/...;base64,...` yang valid dan terbaca oleh Dompdf tanpa memicu network error.
- **AC-KOP-05**: Strict check: Tidak ada kolom berbau rekening perbankan pada tabel `churches` maupun transaksi.

### Sub-Task B: Pengaturan Identitas Gereja di Filament (Byte & Pixel)
- **AC-KOP-06**: Pengguna ber-role `church_admin` dapat membuka dan mengedit profil gerejanya sendiri di panel admin.
- **AC-KOP-07**: Pengguna `church_admin` dilarang mengubah kode gereja (`code`) atau mengakses data gereja lain (403 Forbidden).
- **AC-KOP-08**: Upload file logo bekerja dengan preview langsung, menerima format PNG/JPG/SVG dengan batas ukuran maksimal 2MB.

### Sub-Task C: Komponen Kop Surat & Standarisasi PDF (Pixel & Byte)
- **AC-KOP-09**: Komponen `pdf.components.letterhead` berhasil di-render di Dompdf dengan proporsi: logo di sebelah kiri (tinggi maks 70px) dan teks identitas (Sinode, Nama Gereja, Alamat, Telp, Email) di tengah/rata tengah.
- **AC-KOP-10**: Garis pemisah ganda kop surat (double line: tebal 2px + tipis 1px) tampil rapi pada hasil cetak PDF.
- **AC-KOP-11**: Jika gereja tidak memiliki logo (`logo_path` null), layout kop secara otomatis beralih ke format teks terpusat tanpa menyisakan ruang kosong yang janggal (*zero layout shift*).
- **AC-KOP-12**: Seluruh 7 modul laporan di `BaseReportPage` yang menggunakan `pdf.report` otomatis memiliki kop surat resmi gereja aktif.
- **AC-KOP-13**: Seluruh 5 dokumen sakramen/lifecycle (`akta-nikah`, `akta-lahir`, `dokumen-baptis-anak`, `dokumen-sidi`, `surat-kematian`) menampilkan kop surat resmi yang seragam.

### Sub-Task D: Backend Keuangan per Kantong & Snapshot Warta (Byte)
- **AC-WARTA-01**: `WartaJemaat::getReportData()` mengembalikan struktur `fundBreakdowns` yang mengelompokkan transaksi per `Fund` (kantong kas).
- **AC-WARTA-02**: Perhitungan saldo awal tiap kantong sebelum `$startDate` akurat dan dihitung dengan query agregat optimal (tanpa problem N+1).
- **AC-WARTA-03**: Pemasukan (`debit`) dan Pengeluaran (`credit`) tiap kantong terkelompokkan secara benar berdasarkan `FinancialCategory`.
- **AC-WARTA-04**: Saldo akhir kantong konsisten: `Saldo Awal + Total Masuk - Total Keluar = Saldo Akhir`.
- **AC-WARTA-05**: Data konsolidasi global (`openingBalance`, `totalIncome`, `totalExpenses`, `closingBalance`) tetap tersedia di root array untuk mempertahankan kompatibilitas ke belakang (100% backward compatible).
- **AC-WARTA-06**: `WartaPublishController::snapshot()` menyimpan array `church` dan `finance.funds` ke dalam `warta_publications.content`.
- **AC-WARTA-07**: Export Excel dan PDF pada warta memuat lembar/tabel rincian per kantong.

### Sub-Task E: UI Warta Keuangan di Admin, Publik & Portal (Pixel)
- **AC-WARTA-08**: Halaman Warta Admin (`filament.pages.warta-jemaat`) menampilkan 4 kartu konsolidasi dan daftar kartu/tabel per kantong dengan pemisahan warna tegas (Hijau untuk Pemasukan, Merah untuk Pengeluaran, Amber/Gold untuk Saldo Akhir).
- **AC-WARTA-09**: Halaman Warta Publik (`public.warta.show`) menyajikan laporan per kantong yang mudah dipahami oleh jemaat awam di perangkat mobile maupun desktop.
- **AC-WARTA-10**: Halaman Detail Warta Portal Mandiri Anggota (`portal.warta-detail`) menyajikan format laporan per kantong yang konsisten dengan halaman publik.
- **AC-WARTA-11**: Saat dicetak via tombol Cetak Browser (`window.print()`), elemen kontrol filter dan tombol aksi tersembunyi (`print:hidden`), dan laporan keuangan tercetak rapi dalam lembar A4.
- **AC-WARTA-12**: Tidak ada istilah nomor rekening, transfer bank, atau giro pada seluruh halaman warta.

### Sub-Task F: Keamanan, Isolasi Tenant, & Regresi (QA & Security)
- **AC-TEST-01**: Isolasi tenant 100% terjamin: Transaksi, kantong, publikasi warta, dan identitas gereja A tidak bocor ke gereja B.
- **AC-TEST-02**: Semua test existing (`ReportFase3ATest`, `WartaPublicationTest`, `WartaJemaatTest`, `MemberPortalTest`) tetap lulus 100% hijau (*no regressions*).
- **AC-TEST-03**: Ekspor PDF tidak pernah menghasilkan error 500 akibat file logo tidak ditemukan atau kesalahan konfigurasi `isRemoteEnabled`.

---

## 9. Catatan Keamanan, Non-Functional Requirements & Pitfalls

1. **Keamanan Dompdf (SSRF & Remote Access)**:
   - Tetap pertahankan `'isRemoteEnabled' => false` di `ReportExporter.php`. Jangan pernah mengubah menjadi `true` demi menghindari kerentanan SSRF (Server-Side Request Forgery).
   - Selalu gunakan `$church->logo_base64` atau jalur lokal filesystem `storage_path('app/public/...')` yang sudah divalidasi keberadaan filenya (`file_exists`).
2. **Validasi File Logo**:
   - Pastikan validasi upload logo di backend hanya menerima file gambar asli (PNG, JPG, SVG).
   - Terapkan sanitasi SVG jika mengizinkan upload SVG guna mencegah serangan SVG XSS (atau batasi hanya PNG dan JPG jika ingin 100% aman).
3. **Null-Safety pada Periode Kosong**:
   - Jika pada periode warta tertentu tidak ada transaksi sama sekali, saldo akhir kantong harus tetap sama dengan saldo awal, dan daftar pos pemasukan/pengeluaran menampilkan state kosong yang ramah (*"Tidak ada transaksi pada periode ini"*), bukan error `null pointer` atau pembagian nol.
4. **Isolasi Multi-Tenant**:
   - Query kantong (`Fund`) dan kategori finansial wajib menerapkan scope gereja aktif (`$this->scopeToActiveChurch(...)`) agar majelis tidak melihat kantong kas milik gereja lain.
5. **Prinsip Murni Kas Tunai**:
   - Seluruh tim (Backend, Frontend, QA) wajib menolak penambahan fitur nomor rekening, QRIS dinamis bank, atau form mutasi rekening bank pada fase ini. Semua alur kas harus diposisikan sebagai transaksi tunai fisik gereja.
