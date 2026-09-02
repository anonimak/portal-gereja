# Jadwal Kerja Tim — Portal Gereja

## Kerangka Kerja Nova — 4 Task/Hari (kebijakan resmi owner)
- Maksimal **4 task per hari**: 2 task jam **07:00** + 2 task jam **16:00**.
- Dalam satu slot, task dikerjakan **berurutan**: task pertama selesai dulu (CI hijau + review Vera + merge), baru task kedua dimulai.
- Schedule SELALU tertulis di repo (ground truth), bukan diingat saja.
- Semua status wajib diverifikasi remote (`gh pr view` + `git log origin/master`) sebelum dilaporkan.
- Preview sore selalu dari HEAD `main`/`master` terbaru → deploy ke http://192.168.1.8:8000

**Format jadwal:** `[Tanggal, Hari] [HH:MM] — Nama Task — Owner — Branch — PR target — DoD (CI hijau + review Vera + merge PIC)`

---

## Ground Truth (Rabu, 2 Sep 2026 · origin/master `5e51848`)
- **Fase 3B TUNTAS (T1–T11 merged):** T5 Kelahiran (#21) · T6 Baptis Anak (#23/#26) · T7 Pra-Sidi (#25) · T8 Sidi/Baptis Dewasa (#28) · T9 Pernikahan (#29) · T10 Bimbingan Pra-Nikah (#33) · T11 Kematian (#34).
- **Publikasi Warta:** implementasi **PR #36 `byte/publikasi-warta` OPEN** (dibuat 12:53 WIB) — migrasi/model/policy/resource/controller/blade/test sudah ditulis; sisa = CI + review Vera + merge.
- **PR #35** (planning docs lama) OPEN → **di-supersede** jadwal detil ini.
- Backlog belum dikerjakan: **Super Admin pemilih gereja + Laporan Keuangan per Dana/Kas** dan **rombak laporan Fase 3A** (spec `analisis/SPEC-FASE3A-LAPORAN.md` sudah di master).

---

## Jadwal Detil

### Rabu, 2 September 2026 (HARI INI) — sisa slot 16:00
- **[Rabu, 2 Sep] 16:00 — Task Sore 1: Publikasi Warta ke jemaat (portal publik) — finalisasi PR #36** — Owner: Byte (backend) + Pixel (frontend/styling) — Branch: `byte/publikasi-warta` (sudah ada) — PR: **#36** (OPEN) — DoD: CI hijau di #36 → review Vera (`gh pr review 36 --approve`) → merge PIC → halaman publik live.
  - Langkah: 16:00–16:10 verifikasi CI #36 · 16:10–17:15 tutup gap review Vera (bila ada) · 17:15–17:30 cek CI ulang · 17:30–18:00 approve + merge.
- **[Rabu, 2 Sep] (setelah Task Sore 1 selesai) 16:00+ — Task Sore 2: Super Admin pemilih gereja (select "Satu gereja"/"All") + Laporan Keuangan per Dana/Kas** — Owner: Byte (backend) — Branch: `byte/f3a-superadmin-select-lapkeu` (baru) — PR: baru via `gh pr create` — DoD: CI hijau + review Vera + merge PIC.
  - Scope: session `active_church_id`; select gereja di laporan (All = perilaku saat ini, satu gereja = scope data); halaman **Laporan Keuangan per Dana/Kas** + export (spec Fase 3A §4 & §9).
- **[Rabu, 2 Sep] Setelah slot 16:00 selesai (2 hal WAJIB):** (1) planning Task 1 & 2 Kamis pagi → simpan di repo; (2) **build preview web** dari HEAD master terbaru → http://192.168.1.8:8000.

### Kamis, 3 September 2026 — 4 task (2 pagi + 2 sore)
- **[Kamis, 3 Sep] 07:00 — Task 1: Rombak Warta Jemaat (layout modern, PDF + Excel)** — Owner: Byte (backend export) + Pixel (layout/styling) — Branch: `byte/f3a-warta-rombak` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §2).
- **[Kamis, 3 Sep] (setelah Task 1) 07:00+ — Task 2: Rombak Laporan Rapat (agenda, peserta, notulen, keputusan, lampiran) + tabel `meeting_minutes`** — Owner: Byte — Branch: `byte/f3a-laporan-rapat` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §3).
- **[Kamis, 3 Sep] 16:00 — Task 3: Laporan Jemaat/Anggota (isi, struktur, export)** — Owner: Byte — Branch: `byte/f3a-laporan-jemaat` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §1).
- **[Kamis, 3 Sep] (setelah Task 3) 16:00+ — Task 4: Laporan Kehadiran Ibadah per periode** — Owner: Byte — Branch: `byte/f3a-laporan-kehadiran` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §1).
- **[Kamis, 3 Sep] Setelah slot sore selesai:** planning Task 1 & 2 Jumat pagi + build preview web dari master terbaru.

### Jumat, 4 September 2026 — 4 task (2 pagi + 2 sore)
- **[Jumat, 4 Sep] 07:00 — Task 1: Laporan Sakramen/Lifecycle** — Owner: Byte — Branch: `byte/f3a-laporan-sakramen` — PR: baru — DoD: CI + Vera + merge (spec Fase 3A §1).
- **[Jumat, 4 Sep] (setelah Task 1) 07:00+ — Task 2: Laporan Pelayan/Official** — Owner: Byte — Branch: `byte/f3a-laporan-pelayan` — PR: baru — DoD: CI + Vera + merge (spec Fase 3A §1).
- **[Jumat, 4 Sep] 16:00 — Task 3: Soft delete lanjutan / audit trail (tutup gap Fase 2 Task 1: `church_id` di `audit_logs`, model scope, `AuditLogResource`, `RestoreAction`, `TrashedFilter`, FK `restrictOnDelete`)** — Owner: Byte — Branch: `byte/f2-audit-gap` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] (setelah Task 3) 16:00+ — Task 4: Notifikasi (email) — ulang tahun & jadwal ibadah** — Owner: Byte — Branch: `byte/notifikasi-email` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] Setelah slot sore selesai:** planning Senin pagi + build preview web dari master terbaru.

---

## Backlog Berikutnya (belum dijadwalkan — kandidat setelah backlog F3A/F2 terselesaikan)
- Donasi/persembahan online (QRIS/VA).
- Kalender ibadah + event berulang.
- Import/export CSV jemaat & analitik demografi.
- Pengingat/roster bentrok (deteksi konflik jadwal pelayan).
- Publikasi portal mandiri anggota / API.
- Notifikasi WhatsApp; backup & dokumentasi deployment.

## Alur kerja tiap task
Branch sendiri (`byte/<slug>`) → push → buka PR via `gh pr create` → CI hijau → review Vera (`gh pr review`) → approval Vera → merge oleh PIC (Vera). Tidak ada merge oleh non-PIC.
