# HASIL TEST — FASE 1 (T1–T7) — Byte (Backend)

> Tanggal: 2026-08-30
> Repo: `/home/anonimak/.hermes/workspace-main/portal-gereja/` (repo bersama)
> Environment: PHP 8.4.4 (`/home/anonimak/bin/php`), PHPUnit 11.5.55, DB `sqlite :memory:`, vendor ter-install.
> Perintah: `php vendor/bin/phpunit`

## Ringkasan hasil

| Metrik | Nilai |
|---|---|
| Total test | **29** |
| Passed | **29** |
| Failed | **0** |
| Errors | **0** |
| Risky | **0** |
| Assertions | **88** |

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.4
Configuration: /home/anonimak/.hermes/workspace-main/portal-gereja/phpunit.xml

.............................                                     29 / 29 (100%)

Time: 00:01.314, Memory: 68.50 MB

OK (29 tests, 88 assertions)
```

## Yang dikerjakan (sesuai instruksi Nova — Fase 1 final)

### 1. `database/factories/EventRosterFactory.php` — fix resolusi church_id
- **Akar masalah:** Laravel Factory mengonversi atribut bertipe `Model` (mis. `Event` instance) menjadi `int` (`getKey()`) **sebelum** closure `definition()` dieksekusi. Akibatnya `$event instanceof Event` di dalam closure tidak pernah benar, dan `event_id` berupa `int` tidak di-resolve → `church_id` null → `NOT NULL constraint failed: members.church_id`.
- **Perbaikan:** `rosterChurchId()` kini:
  1. `church_id` eksplisit (instance `Church` / int) menang.
  2. `event_id` eksplisit (instance `Event` / int) → resolve ke `church_id` via `Event::query()->withoutGlobalScopes()->find($id)?->church_id` (pakai `withoutGlobalScopes` agar tidak terganggu scope church aktor saat test `actingAs`).
  3. Fallback ke `cachedEventChurchId` (event dibuat oleh factory ini sendiri).
- Ditambah 2 test baru yang membuktikan jalur INT: `test_factory_resolve_church_dari_event_id_int` & `test_factory_resolve_church_dari_member_id_int` (di `BelongsToChurchGuardTest`).

### 2. Konsistensi factory lain (pola serupa)
- `database/factories/MemberSacramentFactory.php` — `sacramentChurchId()` diperbaiki sama: `member_id` int kini di-resolve ke church via `Member::query()->withoutGlobalScopes()->find($id)?->church_id` (sebelumnya salah mengembalikan `member_id` sebagai `church_id`).
- `database/factories/EventFactory.php` — `category_id` kini memakai `resolveChurchId($attributes)` (menghormati `church_id` eksplisit), bukan selalu `churchId()` yang bisa membuat gereja baru.
- `database/factories/TransactionFactory.php` — `fund_id` & `category_id` kini memakai `resolveChurchId($attributes)` (sama, mencegah silang-gereja saat `church_id` di-set eksplisit).
- `MemberFactory` sudah konsisten (family_id memakai `$attributes['church_id'] ?? churchId()`) — tidak diubah.

### 3. `tests/Feature/ExportRouteTest.php` — export route (302/200)
- Test WIP (bypass observer via `DB::table()->update(['role' => 'reader'])`) sudah benar dan **PASS** (4 test, 12 assertions).
- **Akar masalah 500 ditelusuri:** `routes/web.php` memakai `app(LaporanRapatPage::class)` di luar lifecycle Livewire + `$page->data = request()->only(...)`. Setelah ditelusuri:
  - `exportToExcel()`/`getReportData()` **tidak menyentuh `$this->form`** → tidak ada error "typed property uninitialized".
  - Middleware `verified` **no-op** karena `User` tidak implement `MustVerifyEmail` → tidak redirect ke `verification.notice` yang tidak ada.
  - Route sudah mengembalikan **302** (unauthenticated → `/login`) dan **200** (authorized) — dibuktikan lewat test HTTP penuh (bukan asumsi).
- Tidak ada perubahan tambahan pada route; guard role (`super_admin`/`church_admin`/`finance_admin` → 403 untuk `reader`) sudah benar.

### 4. Fix 2 test RISKY (tanpa assertion)
- `tests/Feature/TenantIsolationTest.php::test_policy_menolak_idor_lintas_gereja` — `Gate::forUser(...)->denies()/allows()` dibungkus `$this->assertTrue(...)`.
- `tests/Feature/UserEscalationTest.php::test_user_policy_hanya_super_admin` — sama, dibungkus `$this->assertTrue(...)`.
- Hasil: 0 risky.

## Daftar file diubah
| File | Perubahan |
|---|---|
| `database/factories/EventRosterFactory.php` | Resolusi church_id dari event_id int/instance + cache fallback |
| `database/factories/MemberSacramentFactory.php` | Resolusi church_id dari member_id int/instance |
| `database/factories/EventFactory.php` | `category_id` hormati church_id eksplisit |
| `database/factories/TransactionFactory.php` | `fund_id`/`category_id` hormati church_id eksplisit |
| `tests/Feature/BelongsToChurchGuardTest.php` | +2 test jalur INT (event_id/member_id) |
| `tests/Feature/TenantIsolationTest.php` | Risky → assertion nyata |
| `tests/Feature/UserEscalationTest.php` | Risky → assertion nyata |
| `tests/Feature/ExportRouteTest.php` | (WIP sudah benar, dipertahankan) |
| `.gitignore` | Tambah `/backups` (dump SQL QA jangan di-commit) |
| `analisis/HASIL-TEST-FASE1.md` | File ini |

## Catatan
- `analisis/REVIEW-FASE1.md` adalah dokumen QA Vera (BLOCKED interim) yang ikut ter-commit via `git add -A`; Vera akan update ke APPROVED setelah memverifikasi commit ini.
- Test dijalankan ulang **setelah** semua perubahan; hasil hijau penuh.
