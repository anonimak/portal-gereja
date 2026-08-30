# REVIEW FASE 1 — QA Gate FINAL (Vera) — Eksekusi Nyata

> **Repo:** `~/.hermes/workspace-main/portal-gereja/` (repo bersama, path tunggal tim)
> **HEAD saat review:** `581821e` (test: tambah kasus export guard) — `git rev-parse HEAD` ✅
> **Status Byte:** commit final **BELUM ADA** — 2 file WIP masih unstaged
> (`database/factories/EventRosterFactory.php`, `tests/Feature/ExportRouteTest.php`)
> **Status artefak:** `analisis/HASIL-TEST-FASE1.md` **BELUM ADA**
> **Metode:** EKSEKUSI NYATA — `php vendor/bin/phpunit` dijalankan sendiri (bukan klaim tim),
> PHP 8.4.4 (`/home/anonimak/bin/php`), DB `sqlite :memory:`, PHPUnit 11.5.55.
> **Tanggal review:** 2026-08-30

---

## 0) Status Proses (penting)

| Item | Status |
|---|---|
| Commit Byte terbaru (`git log --oneline -5`) | `581821e` — fix terakhir **belum di-commit** (unstaged) |
| `analisis/HASIL-TEST-FASE1.md` | ❌ tidak ada |
| `git status` | 🔶 **TIDAK bersih** — 2 file modified (WIP Byte) |
| Suite test dijalankan sendiri | ❌ **MERAH**: 1 error + 2 risky (27 tests) |

Sesuai instruksi Nova: karena commit Byte belum ada, deliverables review ini adalah
**(a) checklist QA berbasis AC Fase 1** + **(b) hasil eksekusi suite saat ini** sebagai
bukti gate. Verdict final APPROVED **belum bisa** diberikan sampai Byte commit + re-run hijau.

---

## 1) Checklist QA Fase 1 (berbasis ACCEPTANCE-CRITERIA-FASE1.md)

### T1 — Isolasi church_id
| AC | Pemeriksaan | Status (kode + test) |
|---|---|---|
| AC-T1-01 | Migrasi `church_id` nullable→NOT NULL + index di `member_sacraments` & `event_rosters` | 🟡 **BELUM penuh** — kolom ada + index, tapi **tetap nullable** (no migrasi NOT NULL lanjutan) |
| AC-T1-02 | Backfill dari relasi parent (bukan NULL/hardcode) | ✅ migrasi `2026_03_09_000001` backfill via parent (portabel) |
| AC-T1-03 | Trait `BelongsToChurch` di `MemberSacrament` & `EventRoster` | ✅ `use BelongsToChurch, HasFactory;` di kedua model |
| AC-T1-04 | Global scope non-super_admin; super_admin bebas; auto-fill creating | ✅ `BelongsToChurchGuardTest` hijau (2/2 pass, 1 error di factory test) |
| AC-T1-05 | Widget `StatsOverview` & `CashFlowChart` ter-scope | ✅ `WartaJemaatTest::test_stat_widget...` pass |
| AC-T1-06 | `SacramentsRelationManager` & roster EventResource ter-scope | ✅ kode + factory satu-gereja (test factory ❌ error, lihat §3) |
| AC-T1-07 | Warta: 4 query ter-scope | ✅ `WartaJemaatTest` pass (events/sakramen/birthday/transaksi A saja) |
| AC-T1-08 | Anti crafted POST cross-church; `events.church_id` NOT NULL | 🟡 forcing di trait `creating` ✅; **`events.church_id` masih nullable** (belum ada migrasi) |
| AC-T1-09 | Accept utama: query apa pun tidak memuat gereja lain | ✅ `TenantIsolationTest` pass (counts + find null lintas gereja + super_admin 2x) |

### T2 — RBAC + Policy
| AC | Status |
|---|---|
| AC-T2-01 Policy ada & terdaftar (14 policy + Gate::policy) | ✅ `app/Policies/` 14 file; `Gate::policy` di `AppServiceProvider` |
| AC-T2-02 System cluster super_admin only | ✅ `SystemCluster::canAccess()` + `UserResource::can*`; sebagian diverifikasi test policy |
| AC-T2-03 finance_admin dibatasi | 🟡 tidak ada test khusus finance_admin (perlu tambahan) |
| AC-T2-04 `canAccessPanel` batasi role | ✅ kode; test export guard `reader` → 403 pass |
| AC-T2-05 Actions hormati policy | ✅ policy ada; sebagian via `UserEscalationTest` |
| AC-T2-06 Halaman report & route export terproteksi | ✅ `ExportRouteTest` (unauth→302, admin A→OK, B tidak bocor, reader→403) |

### T3 — Fix Privilege Escalation
| AC | Status |
|---|---|
| AC-T3-01 Validasi server-side role | ✅ `UserEscalationTest` pass (super_admin, gereja lain, role invalid → HttpException) |
| AC-T3-02 church_admin tak bisa edit/delete user gereja lain / super_admin | ✅ test pass |
| AC-T3-03 church_id fallback & tidak bisa pindah gereja | ✅ `UserObserver` + `mutateFormDataBeforeCreate`; test pass |
| AC-T3-04 Password `dehydrated(false)` saat edit | ✅ kode |
| AC-T3-05 DeleteAction & BulkDelete terproteksi | ✅ `UserPolicy` (super_admin tak bisa delete diri sendiri) |

### T4 — Fix Crash Warta
| AC | Status |
|---|---|
| AC-T4-01 Roster null-safe (member/official/placeholder) | ✅ `WartaJemaatTest::test_get_report_data_tidak_crash...` pass (roster official tanpa member) |
| AC-T4-02 `minister_name` dihapus | ✅ grep 0 di app/resources/factories (hanya di `down()` migrasi) |
| AC-T4-03 Guard `Carbon::parse(null)` | ✅ `whereNotNull('birth_date')` + guard |

### T5 — Enum keuangan debit/credit
| AC | Status |
|---|---|
| AC-T5-01 Options `debit`/`credit` | ✅ `FinanceTypeTest` pass (0 kategori in/out; seeder 7 debit/8 credit) |
| AC-T5-02 Kategori UI muncul di form transaksi | ✅ konsisten `debit`/`credit` di resource/factory/seeder |
| AC-T5-03 Alur kategori→transaksi→laporan | ✅ `FinanceTypeTest` pass (income/expense sum) |
| AC-T5-04 Badge/label konsisten | ✅ |

### T6 — Fix Filament v5 BadgeColumn
| AC | Status |
|---|---|
| AC-T6-01 `grep BadgeColumn app/` = 0 | ✅ 0 match |
| AC-T6-02 `->colors(` = 0 di kolom | ✅ |
| AC-T6-03 Pola `TextColumn->badge()->color()` valid | ✅ kode |
| AC-T6-04 Import tidak stale | ✅ |
| AC-T6-05 Accept utama (resource render tanpa error) | 🟡 **belum diverifikasi runtime** (tidak ada test render resource; hanya inspeksi) |

### T7 — DevOps (sudah di-review commit `013d1fc`/`c270cea`/`8c9b508`)
| AC | Status |
|---|---|
| AC-T7-01..05 (secrets, APP_KEY, Dockerfile, queue/scheduler, nginx) | ✅ APPROVED sebelumnya (review `8c9b508`/`c270cea`) |
| AC-T7-06 Smoke `docker compose up` | 🟡 **belum diverifikasi ulang di gate ini** (milik Ray/podman stack) |

---

## 2) Hasil Eksekusi Suite — SAYA JALANKAN SENDIRI (bukan klaim Byte)

Command: `/home/anonimak/bin/php vendor/bin/phpunit --no-coverage`
Config: `phpunit.xml` (sqlite `:memory:`, RefreshDatabase tiap test)

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.
Runtime: PHP 8.4.4
Configuration: /home/anonimak/.hermes/workspace-main/portal-gereja/phpunit.xml

...E...........R.......R...   27 / 27 (100%)
Time: 00:01.257, Memory: 70.50 MB

ERRORS!
Tests: 27, Assertions: 64, Errors: 1, Risky: 2.
```

**Hasil per class (saya jalankan ulang satu per satu):**

| Test class | Tests | Hasil |
|---|---|---|
| BelongsToChurchGuardTest | 3 | ❌ **1 ERROR** (2 pass) |
| TenantIsolationTest | 4 | ⚠️ 1 RISKY (no assertions) |
| UserEscalationTest | 7 | ⚠️ 1 RISKY (no assertions) |
| FinanceTypeTest | 4 | ✅ OK (8 assertions) |
| WartaJemaatTest | 3 | ✅ OK (14 assertions) |
| ExportRouteTest | 4 | ✅ OK (12 assertions) |
| ExampleTest (Unit+Feature) | 2 | ✅ OK |

**Kesimpulan eksekusi: ❌ TIDAK 0 failure / 0 error / 0 risky.** Gate TIDAK lolos.

---

## 3) Akar masalah (diagnosis dari stack trace + sumber vendor)

### 🔴 Error — `BelongsToChurchGuardTest.php:61` → `EventRosterFactory.php:70`
```
SQLSTATE[23000]: NOT NULL constraint failed: members.church_id
insert into "members" ("church_id", "family_id", ...) values (?, 1, ...)  -- church_id = NULL
```
- Pemicu: `EventRoster::factory()->create(['event_id' => $event])` — `event_id` di-pass sebagai **model instance**.
- Mekanisme (vendor `Factory::expandAttributes`, baris 566–598): nilai atribut yang berupa `Model` diubah ke `getKey()` **sebelum closure berikutnya dipanggil**. Jadi saat closure `church_id`/`member_id` dijalankan, `$attributes['event_id']` sudah berupa **int**, bukan `Event`.
- `EventRosterFactory::rosterChurchId()` (baris 28–48, WIP unstaged) hanya menangani `$event instanceof Event` dan `is_numeric($church)` — **tidak menangani `event_id` berupa int**. Karena `cachedEventChurchId` juga tidak terisi (closure `event_id` di-skip karena nilai sudah diberikan), hasilnya `null` → member dibuat dengan `church_id=null` → NOT NULL violation.
- **Artinya fix EventRosterFactory Byte (unstaged) BELUM tuntas** — persis skenario yang diuji test baru masih gagal.

### ⚠️ Risky 1 — `TenantIsolationTest.php:110` `test_policy_menolak_idor_lintas_gereja`
- Memakai `Gate::forUser($adminA)->denies(...)` / `->allows(...)` **tanpa assertion** → PHPUnit: "This test did not perform any assertions". Harus dibungkus `$this->assertTrue(Gate::forUser(...)->denies(...))` / `assertFalse(...)`.

### ⚠️ Risky 2 — `UserEscalationTest.php:122` `test_user_policy_hanya_super_admin`
- Pola sama: `Gate::forUser(...)->denies(...)` / `->allows(...)` tanpa assertion → risky. Perlu `assertTrue`/`assertFalse`.

Catatan: kedua "risky tests" ini berada di scope kerja Byte ("risky tests" yang ia sebut sedang diperbaiki) — **belum selesai**.

---

## 4) Verdict per Task

| Task | Verdict | Bukti |
|---|---|---|
| T1 Isolasi church_id | 🔴 **NEEDS_FIX** | Tenant/Warta/widget test hijau, tapi factory roster masih error (NOT NULL) → T1-06/T1-09 belum 100% |
| T2 RBAC + Policy | 🟡 **NEEDS_FIX (minor)** | Policy & guard pass; 1 risky test (belum assert) + finance_admin belum dites |
| T3 Escalation | 🟡 **NEEDS_FIX (minor)** | Semua skenario pass; 1 risky test (belum assert) |
| T4 Crash Warta | ✅ **APPROVED** | WartaJemaatTest 3/3 pass (roster official, isolasi, widget) |
| T5 Enum keuangan | ✅ **APPROVED** | FinanceTypeTest 4/4 pass |
| T6 Filament v5 | ✅ **APPROVED (statis)** | grep 0 BadgeColumn/colors; runtime render belum dites |
| T7 DevOps | ✅ **APPROVED (sebelumnya)** | review commit 013d1fc/c270cea/8c9b508; smoke podman milik Ray |

**Verdict keseluruhan saat ini: 🔴 BLOCKED** — karena suite yang saya jalankan sendiri menghasilkan
**1 error + 2 risky**, bukan 0/0/0, dan commit Byte + `HASIL-TEST-FASE1.md` belum ada.

---

## 5) Yang WAJIB diperbaiki sebelum APPROVED (gate)

**Prioritas 1 (blocker):**
1. **Fix `EventRosterFactory`** agar `event_id` berupa **int** (hasil konversi model) tetap bisa di-resolve ke `church_id`
   — mis. `if (is_numeric($event)) return Event::find($event)?->church_id;` (atau resolve via `$attributes['church_id']` numeric yang sudah di-set). — `database/factories/EventRosterFactory.php:28-48`
2. **Fix 2 risky tests** → bungkus `Gate::forUser(...)->denies/allows` dengan `assertTrue/assertFalse`.
   — `tests/Feature/TenantIsolationTest.php:110-124`, `tests/Feature/UserEscalationTest.php:122-130`
3. **Byte commit** fix-nya + tulis `analisis/HASIL-TEST-FASE1.md`; `git status` harus bersih.

**Prioritas 2 (sebelum owner sign-off, non-blocker commit):**
4. Tambah migrasi `church_id` **NOT NULL** di `member_sacraments` & `event_rosters` (AC-T1-01) + `events.church_id` NOT NULL (AC-T1-08).
5. Tambah test khusus `finance_admin` (AC-T2-03) & test render resource Filament (AC-T6-05 runtime).
6. Verifikasi smoke podman (AC-T7-06) oleh Ray + catat hasilnya di file yang sama.

---

## 6) Catatan sinkronisasi workspace

- Repo bersama `/home/anonimak/.hermes/workspace-main/portal-gereja/` = **sumber kebenaran** ✅.
- Sandbox `workspace-vera` tertinggal (HEAD `7cf3828`) dan **tidak dipakai** — konsisten kesepakatan tim.
- HEAD repo bersama `581821e`, 9 commit di depan origin (belum di-push) — **jangan lupa push** setelah commit Byte.

---

*Ditulis oleh Vera (QA Gate) — berbasis eksekusi nyata, bukan inspeksi statis.*
*Status: BLOCKED (interim). Akan di-update ke APPROVED setelah commit Byte + re-run hijau (0 error / 0 failure / 0 risky) + HASIL-TEST-FASE1.md ada + git status bersih.*
