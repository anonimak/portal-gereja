# Jadwal Kerja Tim — Portal Gereja

## Kerangka Kerja Nova — 4 Task/Hari (kebijakan resmi owner)
- Maksimal **4 task per hari** (2 pagi + 2 sore).
- Schedule SELALU tertulis di repo (ground truth), bukan diingat saja.
- Semua status wajib diverifikasi remote (`gh pr view` + `git log origin/master`) sebelum dilaporkan.
- Preview sore selalu dari HEAD `main`/`master` terbaru.

## Jadwal Harian
- **07:00 — Task 1 & 2 (pagi):** kick-off → implementasi → CI → review Vera → laporan singkat.
- **Setelah task pagi selesai:** buat planning + reminder untuk Task 3 & 4 (jam 4 sore), simpan di repo.
- **16:00 — Task 3 & 4 (sore):** kick-off → implementasi → CI → review Vera → laporan singkat.
- **Setelah task sore selesai — 2 hal WAJIB:**
  1. Buat planning Task 1 & 2 untuk besok pagi (simpan di repo).
  2. **BUILD PREVIEW WEB** dari branch `main`/`master` versi TERBARU untuk review owner → deploy ke http://192.168.1.8:8000

## Status Backlog (terverifikasi per `origin/master` 5e51848 — 2026-09-02)

### Fase 3B — SELESAI (T5–T11 merged)
- **T5** Kelahiran + Akta Lahir ✅ (#21)
- **T6** Baptis Anak + Dokumen Baptis ✅ (#23)
- **T7** Bimbingan Pra-Sidi (template topik 1..N, sesi, peserta, pembimbing) ✅ (#25)
- **T8** Sidi / Baptis Dewasa + Dokumen ✅ (#28)
- **T9** Pernikahan + Akta Nikah ✅ (#29)
- **T10** Bimbingan Pra-Nikah (template topik 1..N, sesi, peserta, pembimbing) ✅ (#33)
- **T11** Kematian + Surat Kematian (catat, ubah status, dokumen) ✅ (#34)

### Fase berikutnya (backlog prioritas)
- **Publikasi Warta ke jemaat (portal publik/mobile)** — value tinggi utk client; scope jelas (lihat spec Fase 3A §2 + backlog).
- **Fase 3A — Rombak total modul Laporan** (spec `SPEC-FASE3A-LAPORAN.md` sudah di master): warta modern + export PDF/Excel, laporan jemaat/keuangan per dana/kehadiran/sakramen/pelayan, laporan rapat substantif, **Super Admin pemilih gereja (satu gereja ATAU "All")**.
- Soft delete lanjutan / audit trail (backlog Fase 2).
- dst. (donasi online, notifikasi, kalender ibadah).

> **Catatan penomoran:** Fase 3B selesai penuh di T11. Task berikutnya memakai penomoran fase baru: **Fase 3B selesai; tugas baru masuk ke fase berikutnya (F3A / F4).**

## Agenda Hari Ini (4 task: 2 pagi + 2 sore)

### Pagi — Task 1 & 2 (07:00) ✅ SELESAI
- **Task 1 — T10 Bimbingan Pra-Nikah** ✅ merged (#33)
- **Task 2 — T11 Kematian + Surat Kematian** ✅ merged (#34)
- Master terbaru: `5e51848`. Fase 3B lunas.

### Sore — Task 3 & 4 (16:00) — PLANNING + REMINDER
- **Task 3 — Publikasi Warta ke jemaat (portal publik)** — `byte/publikasi-warta` (Byte backend + Pixel frontend)
  - Scope: halaman publik read-only warta terbaru (route publik tanpa login), link/QR share, render dari data warta existing; styling modern.
  - Dasar: spec Fase 3A §2 + backlog publikasi warta.
  - Alur: branch `byte/publikasi-warta` → push → `gh pr create` → CI hijau → review Vera → approval → merge PIC.
- **Task 4 — Fase 3A tahap 1: Super Admin pemilih gereja + Laporan Keuangan per Dana/Kas** — `byte/f3a-laporan` (Byte backend)
  - Scope: session `active_church_id`, select "satu gereja ATAU All", laporan keuangan per dana/kas + export (spec Fase 3A §4 & §9).
  - Alur: branch `byte/f3a-laporan` → push → `gh pr create` → CI hijau → review Vera → approval → merge PIC.
- **Reminder:** ⏰ **16:00 WIB — kick-off Task 3 & 4** (pilih task mana yg didahulukan; maks 2 task).

### Slot waktu sore (perkiraan 16:00–19:00)
| Waktu | Aktivitas |
|---|---|
| 16:00 | Kick-off Task 3 & 4 |
| 16:15–17:30 | Implementasi + buka PR (Task 3, lalu Task 4) |
| 17:30–18:00 | Cek CI hijau (kedua PR) |
| 18:00–18:30 | Review + approval Vera |
| 18:30–19:00 | Laporan akhir + planning besok pagi |

### Setelah Task 4 selesai — 2 hal WAJIB
1. Planning Task 1 & 2 besok pagi (simpan di repo).
2. **Build preview web** dari HEAD `master` terbaru → http://192.168.1.8:8000 (untuk review owner).

## Alur kerja tiap task
Branch sendiri (`byte/<slug>`) → push → buka PR via `gh pr create` → CI hijau → review Vera (`gh pr review`) → approval Vera → merge oleh PIC (Vera).
