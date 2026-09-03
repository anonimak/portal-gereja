# Jadwal Kerja Tim — Portal Gereja

## Kerangka Kerja Nova — 4 Task/Hari (kebijakan resmi owner)
- Maksimal **4 task per hari**: 2 task jam **07:00** + 2 task jam **16:00**.
- Dalam satu slot, task dikerjakan **berurutan**: task pertama selesai dulu (CI hijau + review Vera + merge), baru task kedua dimulai.

## Aturan Permanen — Schedule-of-Record di Master
1. **Schedule-of-record SELALU di master.** Sebelum hari berganti, master WAJIB sudah memuat agenda untuk hari ini DAN hari berikutnya (tanggal + jam + task + owner). Tidak boleh ada hari tanpa agenda resmi di master.
2. **Aturan Nova (heartbeat blocker):** jika cek heartbeat menemukan tanggal hari ini TIDAK punya agenda di `analisis/SCHEDULE.md` master → ini **BLOCKER** → Nova wajib segera eskala ke owner (bukan "nothing to do").
3. **Ritual akhir slot 16:00:** setelah task sore selesai, Nova langsung mendelegasikan update `SCHEDULE.md` dengan agenda besok (07:00 + 16:00) → PR dibuka → direview & di-merge **di hari yang sama** (docs-only, fast-track). Begitu juga usai slot 07:00 → pastikan agenda sore sudah resmi di master.
4. **Fast-track PR dokumen jadwal:** PR yang hanya mengubah `SCHEDULE.md` tidak boleh menunggu review lama — target **merge di hari yang sama**.
- Schedule SELALU tertulis di repo (ground truth), bukan diingat saja.
- Semua status wajib diverifikasi remote (`gh pr view` + `git log origin/master`) sebelum dilaporkan.
- Preview sore selalu dari HEAD `main`/`master` terbaru → deploy ke http://192.168.1.8:8000

**Format jadwal:** `[Tanggal, Hari] [HH:MM] — Nama Task — Owner — Branch — PR target — DoD (CI hijau + review Vera + merge PIC)`

---

## Ground Truth (Kamis, 3 Sep 2026 · origin/master `7b06a6e`)
- **Fase 3B TUNTAS (T1–T11 merged):** T5 Kelahiran (#21) · T6 Baptis Anak (#23/#26) · T7 Pra-Sidi (#25) · T8 Sidi/Baptis Dewasa (#28) · T9 Pernikahan (#29) · T10 Bimbingan Pra-Nikah (#33) · T11 Kematian (#34).
- **Publikasi Warta ke jemaat (portal publik) ✅ MERGED (#36)** — halaman publik live (snapshot periode, publish admin, halaman publik per gereja), suite 238/238.
- **PR #37 (jadwal detil 2–4 Sep) & PR #35 (planning 16:00)** — masih OPEN → **di-supersede** dokumen ini agar tidak duplikat dan master langsung memuat agenda resmi hari ini + besok.
- Backlog belum dikerjakan: **Super Admin pemilih gereja + Laporan Keuangan per Dana/Kas** dan **rombak laporan Fase 3A** (spec `analisis/SPEC-FASE3A-LAPORAN.md` sudah di master).

---

## Agenda Resmi

### Kamis, 3 September 2026 (HARI INI) — 4 task (2 pagi + 2 sore)
- **[Kamis, 3 Sep] 07:00 — Task 1: Rombak Warta Jemaat (layout modern, PDF + Excel)** — Owner: Byte (backend export) + Pixel (layout/styling) — Branch: `byte/f3a-warta-rombak` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §2).
- **[Kamis, 3 Sep] (setelah Task 1) 07:00+ — Task 2: Rombak Laporan Rapat (agenda, peserta, notulen, keputusan, lampiran) + tabel `meeting_minutes`** — Owner: Byte — Branch: `byte/f3a-laporan-rapat` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §3).
- **[Kamis, 3 Sep] 16:00 — Task 3: Laporan Jemaat/Anggota (isi, struktur, export)** — Owner: Byte — Branch: `byte/f3a-laporan-jemaat` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §1).
- **[Kamis, 3 Sep] (setelah Task 3) 16:00+ — Task 4: Laporan Kehadiran Ibadah per periode** — Owner: Byte — Branch: `byte/f3a-laporan-kehadiran` — PR: baru — DoD: CI hijau + review Vera + merge PIC (spec Fase 3A §1).
- **[Kamis, 3 Sep] Setelah slot 16:00 selesai (2 hal WAJIB):** (1) ritual update `SCHEDULE.md` agenda Jumat + Senin → PR docs-only → review & merge hari yang sama (fast-track); (2) **build preview web** dari HEAD master terbaru → http://192.168.1.8:8000.

### Jumat, 4 September 2026 — 4 task (2 pagi + 2 sore)
- **[Jumat, 4 Sep] 07:00 — Task 1: Laporan Sakramen/Lifecycle** — Owner: Byte — Branch: `byte/f3a-laporan-sakramen` — PR: baru — DoD: CI + Vera + merge (spec Fase 3A §1).
- **[Jumat, 4 Sep] (setelah Task 1) 07:00+ — Task 2: Laporan Pelayan/Official** — Owner: Byte — Branch: `byte/f3a-laporan-pelayan` — PR: baru — DoD: CI + Vera + merge (spec Fase 3A §1).
- **[Jumat, 4 Sep] 16:00 — Task 3: Soft delete lanjutan / audit trail (tutup gap Fase 2 Task 1: `church_id` di `audit_logs`, model scope, `AuditLogResource`, `RestoreAction`, `TrashedFilter`, FK `restrictOnDelete`)** — Owner: Byte — Branch: `byte/f2-audit-gap` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] (setelah Task 3) 16:00+ — Task 4: Notifikasi (email) — ulang tahun & jadwal ibadah** — Owner: Byte — Branch: `byte/notifikasi-email` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] Setelah slot 16:00 selesai:** ritual update `SCHEDULE.md` agenda Senin (07:00 + 16:00) → PR docs-only fast-track → merge hari yang sama + **build preview web** dari master terbaru.

---

## Backlog Berikutnya (belum dijadwalkan — kandidat setelah backlog F3A/F2 terselesaikan)
- Super Admin pemilih gereja (select "Satu gereja"/"All") + Laporan Keuangan per Dana/Kas.
- Donasi/persembahan online (QRIS/VA).
- Kalender ibadah + event berulang.
- Import/export CSV jemaat & analitik demografi.
- Pengingat/roster bentrok (deteksi konflik jadwal pelayan).
- Portal mandiri anggota / API.
- Notifikasi WhatsApp; backup & dokumentasi deployment.

## Alur kerja tiap task
Branch sendiri (`byte/<slug>`) → push → buka PR via `gh pr create` → CI hijau → review Vera (`gh pr review`) → approval Vera → merge oleh PIC (Vera). Tidak ada merge oleh non-PIC.
