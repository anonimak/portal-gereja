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

## Ground Truth (Kamis, 3 Sep 2026 · origin/master `7a34fbf`)

### KOREKSI JADWAL (hasil verifikasi master, bukan dari ingatan)
Agenda PR #38 (Rombak Warta, Rombak Laporan Rapat, Laporan Jemaat/Kehadiran/Sakramen/Pelayan, gap audit F2) **TERNYATA SUDAH ADA di master sejak Fase 3A** → ditandai **DONE**, tidak boleh dikerjakan ulang.

### Yang SUDAH ADA di master (verified via `git ls-tree` + `git grep`)
- **Fase 3A Laporan TUNTAS:** Rombak UI Warta + PDF/Excel (PR #14) · Rombak modul Laporan **7 tipe** — Warta, Jemaat, Keuangan per Dana/Kas, Kehadiran, Sakramen, Pelayan, Rapat (PR #15, `Reporting/Pages/*`) · Fix Laporan Rapat `isAllChurches`/`canSelectChurch` (PR #17) · dark/light laporan (PR #18) · styling export Excel (PR #19) · docker ext-gd dompdf/excel (PR #16). Model `MeetingMinutes` + migrasi ada.
- **Super Admin pemilih gereja (All/satu gereja) + Laporan Keuangan per Dana/Kas:** ✅ ada di `BaseReportPage` (§9 church select) + `LaporanKeuanganPage` (per dana, saldo awal/akhir, export).
- **Fase 2 Task 1 gap audit — HAMPIR TUNTAS:** `church_id` di `audit_logs` ✅ (migrasi `000007`), scope tenant `AuditLog` ✅, FK `restrictOnDelete` ✅ (`000008`), `RestoreAction`/`TrashedFilter` di resource (Members/Transaction/Event/Sacraments/Attendances/Birth/Death/Guidance/Marriage) ✅, `MemberObserver` LOW-4 ✅.
- **Fase 3B TUNTAS (T1–T11):** Kelahiran · Baptis Anak · Pra-Sidi · Sidi · Pernikahan · Pra-Nikah · Kematian (PR #21–#34).
- **Lainnya:** Publikasi Warta portal publik ✅ (#36) · RBAC granular 6 role + ChurchScope ✅ (#30) · Kehadiran per anggota (EventAttendance) ✅.

### Backlog NYATA (verified BELUM ada di master)
1. **AuditLogResource** — UI lihat audit log (System cluster) belum ada; model `AuditLog` + scope sudah siap.
2. **Notifikasi email** (ulang tahun & jadwal ibadah) + scheduler — `app/Notifications`, `app/Mail`, `app/Console` **tidak ada** di master.
3. **Deteksi bentrok roster pelayan** — `grep conflict/bentrok` kosong.
4. **Import/export CSV jemaat** — hanya ada Excel export laporan; import CSV jemaat belum ada.
5. **Kalender ibadah + event berulang (recurring)** — `grep recurring/repeat` kosong.
6. **Portal mandiri anggota / API** — tidak ada `routes/api.php`; baru halaman publik warta.
7. **Donasi online QRIS/VA** — `grep donasi/payment/qris` kosong.
8. **Notifikasi WhatsApp** — belum ada.

---

## Agenda Resmi (pasca-koreksi)

### Kamis, 3 September 2026 (HARI INI)
- **[Kamis, 3 Sep] 07:00 — Task 1: Rombak Warta Jemaat (PDF/Excel)** — ✅ **DONE di master (PR #14)** — tidak dikerjakan ulang.
- **[Kamis, 3 Sep] 07:00+ — Task 2: Rombak Laporan Rapat (+`meeting_minutes`)** — ✅ **DONE di master (PR #15/#17/#18)**.
- **[Kamis, 3 Sep] 16:00 — Task 1 (slot sore): AuditLogResource — UI lihat audit log (System cluster, read-only; super_admin semua gereja, church_admin gereja sendiri)** — Owner: Byte — Branch: `byte/auditlog-resource` — PR: baru — DoD: CI hijau + review Vera + merge PIC (gap Fase 2 Task 1).
- **[Kamis, 3 Sep] (setelah Task 1) 16:00+ — Task 2 (slot sore): Notifikasi email (ulang tahun & jadwal ibadah) + scheduler/queue** — Owner: Byte — Branch: `byte/notifikasi-email` — PR: baru — DoD: CI hijau + review Vera + merge PIC.
- **[Kamis, 3 Sep] Setelah slot 16:00 selesai (2 hal WAJIB):** (1) ritual update `SCHEDULE.md` agenda Jumat + Senin → PR docs-only → review & merge hari yang sama (fast-track); (2) **build preview web** dari HEAD master terbaru → http://192.168.1.8:8000.

### Jumat, 4 September 2026 — 4 task (2 pagi + 2 sore)
- **[Jumat, 4 Sep] 07:00 — Task 1: Deteksi bentrok roster pelayan (validasi tumpang-tindih jadwal per orang)** — Owner: Byte — Branch: `byte/roster-bentrok` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] (setelah Task 1) 07:00+ — Task 2: Import/export CSV jemaat (bulk + template)** — Owner: Byte — Branch: `byte/csv-jemaat` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] 16:00 — Task 3: Kalender ibadah + event berulang (recurring schedule)** — Owner: Byte — Branch: `byte/kalender-ibadah` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] (setelah Task 3) 16:00+ — Task 4: Portal mandiri anggota / API (baca data diri, jadwal, warta)** — Owner: Byte (backend) + Pixel (tampilan) — Branch: `byte/portal-anggota` — PR: baru — DoD: CI + Vera + merge.
- **[Jumat, 4 Sep] Setelah slot 16:00 selesai:** ritual update `SCHEDULE.md` agenda Senin (07:00 + 16:00) → PR docs-only fast-track → merge hari yang sama + **build preview web** dari master terbaru.

---

## Backlog Berikutnya (belum dijadwalkan — kandidat setelah Jumat tuntas)
- Donasi/persembahan online (QRIS/VA).
- Notifikasi WhatsApp.
- Notifikasi/reminder lanjutan & backup-dokumentasi deployment.

## Alur kerja tiap task
Branch sendiri (`byte/<slug>`) → push → buka PR via `gh pr create` → CI hijau → review Vera (`gh pr review`) → approval Vera → merge oleh PIC (Vera). Tidak ada merge oleh non-PIC.
