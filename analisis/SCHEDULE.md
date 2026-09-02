# Jadwal Kerja Tim — Portal Gereja

Aturan pace (dari owner): **MAKSIMAL 2 TASK PER HARI** per fase.

## Status task Fase 3B (verified merged di master)
- ✅ **T5** Kelahiran — PR #21
- ✅ **T6** Baptis Anak — PR #23 (+ fix #26)
- ✅ **T7** Bimbingan Pra-Sidi — PR #25
- ✅ **T8** Sidi / Baptis Dewasa — PR #28
- ✅ **Pernikahan + Akta Nikah** — PR #29

## Sisa backlog Fase 3B (belum dikerjakan)
- **Bimbingan Pra-Nikah** — template topik 1..N (infra template 12 sesi sudah ada dari T7), penjadwalan sesi, peserta, pembimbing Pendeta/Majelis (Byte → Vera)
- **Kematian + Surat Kematian** — ibadah, layanan pendeta, status anggota, penerbitan surat kematian (Byte → Vera)

## Agenda hari ini (jam 4 sore)
- Start **16:00** — **Bimbingan Pra-Nikah** lalu **Kematian + Surat Kematian**. Maks 2 task.

### Slot waktu (perkiraan 16:00–19:00)
| Waktu | Aktivitas |
|---|---|
| 16:00–16:15 | Kick-off 2 task + konfirmasi base master bersih |
| 16:15–17:30 | Implementasi + buka PR (branch `byte/<slug>` → `gh pr create`) |
| 17:30–18:00 | Cek CI hijau |
| 18:00–18:30 | Review Vera (`gh pr review`) + approval |
| 18:30–19:00 | Laporan akhir + status PR |

## Alur kerja tiap task
Branch sendiri (`byte/<slug>`) → push → buka PR via `gh pr create` → CI hijau → review Vera (`gh pr review`) → approval Vera → merge oleh PIC (Vera).
