# SPEC FASE 3A — ROMBAK TOTAL MODUL LAPORAN

- **Versi:** v1.0 (draft untuk review Nova/Vera — belum ada coding)
- **Basis:** master `0c22249` (Fase 2 sudah masuk: SoftDelete+Audit, Kehadiran per anggota, RBAC granular 6 role, fix LOW-4)
- **Status:** analisis & spesifikasi

---

## 0. Konteks & Fakta Kode (dibaca langsung dari master `0c22249`)

**Kondisi saat ini:**
- **Warta Jemaat** — `app/Filament/Clusters/Reporting/Pages/WartaJemaat.php` + blade `resources/views/filament/pages/warta-jemaat.blade.php`. Filter rentang tanggal, 4 blok (Jadwal Pelayanan, Ulang Tahun, Sakramen, Keuangan). Export hanya `window.print()` → bukan file PDF/Excel.
- **Laporan Rapat** — `app/Filament/Pages/LaporanRapatPage.php` (standalone, navigationGroup `Laporan`). Periode bulanan/triwulan. Data: event + roster + kehadiran + keuangan (saldo awal, pemasukan/pengeluaran per kategori, saldo akhir). Export lewat route `POST /admin/laporan-rapat/export-excel` (middleware `auth`+`verified`, guard 3 role) — **sebenarnya CSV** ber-delimiter `;`, bukan XLSX.
- **Dashboard** — `StatsOverview` + `CashFlowChart` (arus kas tahunan). Belum ada pemilih gereja.
- **Multi-tenant** — trait `BelongsToChurch` global scope: non-super_admin ter-scope gereja sendiri, super_admin melihat SEMUA gereja.
- **RBAC** — sudah ada 6 role (super_admin, church_admin, finance_admin, jemaat_admin, warta_editor, report_viewer) + `TenantPolicy` + permission (Task 3).
- **Data tersedia** — Member, Family, MemberSacrament, Official, Event, EventRoster, EventAttendance, EventCategory, MinistryRole, Transaction, Fund, FinancialCategory, AuditLog, User, Church.
- **Library export** — BELUM ada package PDF/Excel (composer.json hanya filament/laravel/tinker).

---

## 1. Arsitektur Umum Laporan Baru

ReportingCluster dirombak menjadi pusat **"Laporan"** dengan 7 halaman:

| # | Halaman | Status |
|---|---------|--------|
| 1 | Warta Jemaat | Rombak total (§2) |
| 2 | Laporan Jemaat / Anggota | Baru (§3) |
| 3 | Laporan Keuangan per Dana/Kas | Baru (§4) |
| 4 | Laporan Kehadiran Ibadah | Baru (§5) |
| 5 | Laporan Sakramen / Lifecycle | Baru (§6) |
| 6 | Laporan Pelayan / Official | Baru (§7) |
| 7 | Laporan Rapat | Rombak total + pindah ke cluster (§8) |

**Fondasi bersama:**
- `App\Traits\HasChurchScope` + `App\Support\ChurchContext` — resolver gereja aktif (super_admin) → `?int $activeChurchId` (null = All). Dipakai semua halaman laporan.
- `App\Services\ReportExporter` — satu pintu export PDF & Excel (data sama persis dengan tampilan: single source `getReportData()`).
- **Keputusan library (perlu approval):** tambah `barryvdh/laravel-dompdf` (PDF) + `maatwebsite/excel` (XLSX). Alternatif bila ditolak: PDF tetap `window.print()` + export tetap CSV (fitur "Excel" dihilangkan).
- Akses halaman memakai **policy server-side** (bukan sekadar hidden UI) — matriks di §1.1.

### 1.1 Matriks akses halaman laporan (role × halaman)

| Halaman | super_admin | church_admin | finance_admin | jemaat_admin | warta_editor | report_viewer |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Warta Jemaat | ✅ | ✅ | ❌ | ❌ | ✅ (view/print/export) | ❌ |
| Laporan Jemaat | ✅ | ✅ | ❌ | ✅ (view) | ❌ | ✅ (view) |
| Laporan Keuangan | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ (view) |
| Laporan Kehadiran | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ (view) |
| Laporan Sakramen | ✅ | ✅ | ❌ | ✅ (view) | ❌ | ✅ (view) |
| Laporan Pelayan | ✅ | ✅ | ❌ | ❌ | ✅ (view) | ✅ (view) |
| Laporan Rapat | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ (view) |

> Catatan: semua role hanya **melihat/men-download** laporan — tidak ada aksi tulis dari halaman laporan (kecuali form filter). Edit data tetap lewat resource asalnya.

---

## 2. Warta Jemaat — Rombak Total UI + Export PDF/Excel

**Tujuan:** layout modern & indah, cocok cetak, bisa diunduh PDF & Excel.

**Konten per blok (urutan tampilan):**
1. **Header** — nama gereja (dari `ChurchContext`), alamat gereja, judul "Warta Jemaat", periode (format `d F Y – d F Y`), label edisi minggu ke-n.
2. **Salam / Renungan singkat** — paragraf statis yang bisa diedit admin (placeholder dulu; lihat Asumsi A5).
3. **Jadwal Ibadah & Pelayanan** — daftar event + jam + lokasi + petugas roster (member/official + peran) + total kehadiran per event (dari `event_attendances`, fallback legacy per AC-T2-10).
4. **Ulang Tahun Jemaat** — nama + tanggal (dari `Member.birth_date`, status aktif, rentang periode).
5. **Perayaan Sakramen** — jenis, tanggal, nama member, pelayan (official), no. sertifikat.
6. **Laporan Keuangan Ringkas** — total pemasukan & pengeluaran periode + daftar transaksi (dari `Transaction`).
7. **Footer** — kop tanda tangan (Pendeta/Sekretaris placeholder), "Diterbitkan oleh Portal Gereja".

**Styling:** Tailwind modern (kartu, gradasi halus), tetap mendukung **dark mode** & **print** (`@media print`, A4, `print:page-break-*`).

**Export:**
- **PDF** — via dompdf (layout A4, font sans, header/kop konsisten). Nama file: `Warta-Jemaat-{periode}.pdf`.
- **Excel** — via maatwebsite/excel, sheet: Ringkasan + detail per blok. Nama file: `Warta-Jemaat-{periode}.xlsx`.
- **Print** — tetap `window.print()` sebagai fallback.

**Filter:** rentang tanggal (default: minggu berjalan Senin–Minggu, konsisten dengan perilaku sekarang).

---

## 3. Laporan Jemaat / Anggota (Baru)

**Isi:**
- Ringkasan: total anggota per status (aktif/titipan/pindah/meninggal), per jenis kelamin, per rentang umur.
- Detail: daftar keluarga + anggota (kelompok per Family) — kolom: NIK, nama, jenis kelamin, tempat/tanggal lahir, alamat (dari Family), hubungan keluarga, status, `custom_fields`.

**Filter:** status, gender, rentang umur, family.

**Struktur data:** agregasi dari `Member` (join `Family`), tanpa tabel baru.

**Export:** Excel (sheet: Ringkasan, Detail per Keluarga) + PDF (tabel ringkas). Nama file: `Laporan-Jemaat-{bulan-tahun}.{pdf,xlsx}`.

---

## 4. Laporan Keuangan per Dana/Kas (Baru)

**Isi:**
- Per `Fund` (kas/dana): saldo awal periode, rincian pemasukan per kategori (`FinancialCategory` type `debit`), rincian pengeluaran per kategori (`credit`), total, saldo akhir.
- Lampiran: daftar transaksi (tanggal, kategori, deskripsi, jumlah).

**Filter:** dana (semua/satu), periode (default bulan berjalan), tipe.

**Struktur data:** agregasi dari `Transaction` (join `Fund`, `FinancialCategory`), tanpa tabel baru.

**Export:** Excel (sheet per dana: Saldo, Pemasukan, Pengeluaran, Rincian) + PDF (ringkasan per dana + lampiran). Nama file: `Laporan-Keuangan-{dana}-{periode}.{pdf,xlsx}`.

---

## 5. Laporan Kehadiran Ibadah (Baru)

**Isi:**
- Per event/acara: daftar anggota hadir (status `hadir`) & tidak hadir (`tidak_hadir`), total, `checked_in_by`/`checked_in_at`.
- Rekap per anggota: jumlah hadir per periode, persentase.
- Ringkasan per periode: rata-rata kehadiran, jumlah per event.

**Filter:** event, rentang tanggal, anggota.

**Struktur data:** `EventAttendance` (join `Event`, `Member`). Data legacy `attendance_male/female` hanya fallback bila event tanpa record (AC-T2-10).

**Export:** Excel (sheet: Per Acara, Rekap Anggota) + PDF. Nama file: `Laporan-Kehadiran-{periode}.{pdf,xlsx}`.

---

## 6. Laporan Sakramen / Lifecycle (Baru)

**Isi:**
- Daftar sakramen per jenis (penyerahan, baptis anak, sidi, baptis dewasa, nikah) dalam periode.
- Kolom: tanggal, jenis, nama member, pelayan (official), no. sertifikat.
- Ringkasan per jenis (jumlah).

**Filter:** jenis sakramen, periode.

**Struktur data:** `MemberSacrament` (join `Member`, `Official`), tanpa tabel baru.

**Export:** Excel + PDF. Nama file: `Laporan-Sakramen-{periode}.{pdf,xlsx}`.

---

## 7. Laporan Pelayan / Official (Baru)

**Isi:**
- Daftar official: tipe (pelayan_tamu / majelis_lokal / pendeta_internal), nama (display_name), asal (origin_church), masa jabatan (start/end), status aktif (end_date null).
- Rekap roster per acara per periode: petugas + peran (`EventRoster` join `MinistryRole`).

**Filter:** tipe official, periode, acara.

**Struktur data:** `Official` + `EventRoster`, tanpa tabel baru.

**Export:** Excel + PDF. Nama file: `Laporan-Pelayan-{periode}.{pdf,xlsx}`.

---

## 8. Laporan Rapat — Rombak Total + Isi Substantif

**Tujuan:** isi bukan hanya angka keuangan, tapi dokumen rapat yang utuh.

**Konten (substantif):**
1. **Header** — gereja, judul rapat, tanggal, periode.
2. **Agenda** — daftar topik rapat.
3. **Peserta** — daftar hadir (nama + peran), jumlah.
4. **Notulen** — narasi jalannya rapat.
5. **Keputusan** — daftar keputusan + status tindak lanjut (terbuka/selesai).
6. **Lampiran** — file lampiran (upload, daftar path).
7. **Lampiran keuangan** — ringkasan pemasukan/pengeluaran/saldo periode (reuse logika LaporanRapat existing).

**Data baru (keputusan scope — lihat Asumsi A2):**
- Tabel `meeting_minutes`: `id`, `church_id`, `event_id` (nullable FK `events`), `title`, `meeting_date`, `agenda` (json), `participants` (json), `notes` (longText), `decisions` (json), `attachments` (json), `softDeletes`, `timestamps`. Model pakai `BelongsToChurch` + `RecordsAuditTrail`.
- Resource/pages minimal: buat/edit notulen per rapat (super_admin/church_admin), tampil di Laporan Rapat.

**UI:** form filter (periode + rapat) + panel notulen yang bisa diedit; tombol export Excel & PDF.

**Export Excel:** multi-sheet (Agenda, Peserta, Notulen, Keputusan, Keuangan). **Export PDF:** dokumen rapat A4.

---

## 9. Super Admin — Pemilih Gereja (Satu Gereja ATAU "All")

**Kebutuhan:** super_admin bisa memilih satu gereja ATAU "All"; semua halaman/resource fokus ke gereja terpilih.

**Desain:**
- **State:** session per user `active_church_id` (null = All), di-set lewat Select di header/topbar panel (atau widget kecil di halaman Laporan).
- **Mekanisme scope:** modifikasi global scope `BelongsToChurch`:
  - non-super_admin → scope gereja sendiri (tidak berubah).
  - super_admin + `active_church_id` terisi → `where('church_id', active)`.
  - super_admin + All → tanpa scope (perilaku sekarang).
- **Cakupan:** semua resource BelongsToChurch (Jemaat, Acara, Keuangan, MasterData, Official, Laporan, Dashboard widget) otomatis fokus ke gereja terpilih karena memakai global scope.
- **Pengecualian eksplisit:** `ChurchResource` (master data gereja) tetap menampilkan semua gereja untuk super_admin — gereja adalah master data lintas tenant.
- **Keamanan:** pemilih gereja HANYA tampil & berlaku untuk super_admin; role lain tetap terkunci ke gereja sendiri (AC-T3 tetap berlaku).

---

## 10. Acceptance Criteria (GIVEN/WHEN/THEN)

### A. Umum / arsitektur laporan
- **AC-3A-01** — GIVEN user dengan role sesuai matriks §1.1, WHEN mengakses halaman laporan, THEN halaman tampil; role di luar matriks → URL langsung 403 (server-side, bukan sekadar hidden).
- **AC-3A-02** — GIVEN data laporan (anggota/keuangan/kehadiran/sakramen/pelayan), WHEN halaman dirender, THEN angka yang ditampilkan sama persis dengan hasil export Excel & PDF (single source `getReportData()`).
- **AC-3A-03** — GIVEN semua laporan, WHEN data di-soft-delete (soft delete), THEN laporan otomatis mengecualikannya (global scope SoftDeletes) tanpa perubahan query manual.

### B. Warta Jemaat
- **AC-3A-04** — GIVEN halaman Warta dengan rentang tanggal, WHEN tombol "Download PDF" ditekan, THEN file `Warta-Jemaat-{periode}.pdf` terunduh berisi 7 blok (§2) dengan layout A4.
- **AC-3A-05** — GIVEN halaman Warta, WHEN tombol "Download Excel" ditekan, THEN file `.xlsx` terunduh berisi data per blok pada sheet terpisah.
- **AC-3A-06** — GIVEN halaman Warta, WHEN dibuka di browser, THEN layout responsif + dark mode + `window.print()` tetap berfungsi.

### C. Laporan Jemaat / Keuangan / Kehadiran / Sakramen / Pelayan
- **AC-3A-07** — GIVEN Laporan Jemaat, WHEN memilih status/gender/umur, THEN ringkasan & detail sesuai filter; Excel berisi sheet Ringkasan + Detail.
- **AC-3A-08** — GIVEN Laporan Keuangan, WHEN memilih dana + periode, THEN saldo awal/akhir, pemasukan & pengeluaran per kategori sesuai transaksi (debit/credit); perhitungan = sum transaksi (bukan hardcode).
- **AC-3A-09** — GIVEN Laporan Kehadiran, WHEN memilih event, THEN daftar hadir/tidak hadir dari `event_attendances`; event tanpa record → fallback legacy `attendance_male+female` (AC-T2-10).
- **AC-3A-10** — GIVEN Laporan Sakramen, WHEN memilih jenis + periode, THEN daftar sakramen beserta pelayan & sertifikat tampil.
- **AC-3A-11** — GIVEN Laporan Pelayan, WHEN membuka laporan, THEN daftar official + status aktif (end_date null) + rekap roster per periode tampil; official member soft-deleted ditandai nonaktif (LOW-4).

### D. Laporan Rapat
- **AC-3A-12** — GIVEN halaman Laporan Rapat, WHEN membuka rapat yang punya notulen, THEN agenda, peserta, notulen, keputusan, lampiran tampil; export Excel multi-sheet & PDF A4 terunduh.
- **AC-3A-13** — GIVEN user super_admin/church_admin, WHEN membuat/ mengedit notulen rapat, THEN tersimpan ke `meeting_minutes` + tercatat di `audit_logs`; role lain ditolak 403.
- **AC-3A-14** — GIVEN Laporan Rapat periode, WHEN memilih bulanan/triwulan, THEN bagian keuangan (saldo awal, pemasukan/pengeluaran per kategori, saldo akhir) tetap akurat (regresi LaporanRapat existing).

### E. Super Admin — pemilih gereja
- **AC-3A-15** — GIVEN super_admin, WHEN memilih gereja X pada selector, THEN semua resource/halaman BelongsToChurch menampilkan hanya data gereja X (termasuk dashboard widget & laporan); memilih "All" → kembali menampilkan semua gereja.
- **AC-3A-16** — GIVEN super_admin memilih gereja X, WHEN membuka `ChurchResource`, THEN tetap menampilkan semua gereja (pengecualian §9).
- **AC-3A-17** — GIVEN role non-super_admin, WHEN membuka halaman mana pun, THEN selector gereja tidak tampil dan data tetap ter-scope ke gereja sendiri (isolasi tenant tidak berubah).
- **AC-3A-18** — GIVEN session aktif `active_church_id`, WHEN user logout/login ulang, THEN state pemilih gereja tidak bocor antar user (scope per-session per-user).

---

## 11. Asumsi Eksplisit

- **A1.** Menambah dependency composer `barryvdh/laravel-dompdf` + `maatwebsite/excel` — butuh approval owner. Bila ditolak: PDF via print browser, "Excel" tetap CSV (fitur XLSX dihilangkan).
- **A2.** Laporan Rapat substantif membutuhkan tabel baru `meeting_minutes` (bukan murni UI) — perlu konfirmasi scope; bila di luar Fase 3A, Laporan Rapat hanya dirombak UI + export tanpa notulen terstruktur.
- **A3.** Pemilih gereja memakai session + modifikasi global scope `BelongsToChurch` (khusus super_admin). `ChurchResource` dikecualikan.
- **A4.** Logo gereja belum tersedia — header warta memakai placeholder; upload logo = task terpisah.
- **A5.** Salam/renungan warta: teks placeholder dulu; penyimpanan konten yang bisa diedit admin = task terpisah bila tidak ingin menambah model.
- **A6.** Semua laporan read-only (hanya lihat + export); edit data tetap lewat resource asal.
- **A7.** Tidak ada perubahan logika keuangan inti; laporan hanya agregasi read.
- **A8.** Export memakai data yang sama dengan tampilan (single source), bukan query terpisah.
- **A9.** Periode default semua laporan = bulan berjalan; filter tanggal/periode tersedia per halaman.
