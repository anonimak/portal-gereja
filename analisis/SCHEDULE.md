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

## Status Backlog (terverifikasi per `origin/master` 3e79f95)

### Fase 3B — Selesai (merged)
- **T5** Kelahiran + Akta Lahir ✅ (#21)
- **T6** Baptis Anak + Dokumen Baptis ✅ (#23)
- **T7** Bimbingan Pra-Sidi (template topik 1..N, sesi, peserta, pembimbing) ✅ (#25)
- **T8** Sidi / Baptis Dewasa + Dokumen ✅ (#28)
- **T9** Pernikahan + Akta Nikah ✅ (#29 — penomoran dirapikan, lihat catatan)

### Fase 3B — Sisa (belum dikerjakan)
- **T10 — Bimbingan Pra-Nikah:** template topik 1..N, penjadwalan sesi, peserta, pembimbing Pendeta/Majelis. Infra template 12 sesi sudah ada dari T7 → tinggal reuse.
- **T11 — Kematian + Surat Kematian:** ibadah, layanan pendeta, status anggota.

### Fase berikutnya (backlog)
- RBAC granular lanjutan (PR #30 sedang OPEN — follow-up, review, merge, tutup gap).
- Soft delete lanjutan / audit trail.
- Publikasi Warta ke jemaat (portal publik/mobile).
- dst. (donasi online, notifikasi, kalender ibadah).

> **Catatan penomoran:** sebelumnya Bimbingan Pra-Nikah = T9 dan Pernikahan = T10. Karena PR #29 sudah merged berlabel **"T9: pernikahan + akta nikah"**, penomoran dirapikan agar sinkron dengan repo: **Pernikahan = T9 (done), Bimbingan Pra-Nikah = T10, Kematian = T11.**

## Agenda Hari Ini (4 task: 2 pagi + 2 sore)
- **07:00 — Task 1:** T10 Bimbingan Pra-Nikah (Byte → Vera)
- **07:00 — Task 2:** T11 Kematian + Surat Kematian (Byte → Vera)
- **16:00 — Task 3:** RBAC granular lanjutan — tutup PR #30 (review/merge + gap) (Byte → Vera)
- **16:00 — Task 4:** Publikasi Warta ke jemaat (portal publik) — fase berikutnya (Byte → Vera)
- Setelah Task 4 selesai: planning Task 1 & 2 besok pagi + **build preview web** dari master terbaru → http://192.168.1.8:8000

## Alur kerja tiap task
Branch sendiri (`byte/<slug>`) → push → buka PR via `gh pr create` → CI hijau → review Vera (`gh pr review`) → approval Vera → merge oleh PIC (Vera).
