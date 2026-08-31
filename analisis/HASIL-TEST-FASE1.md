# HASIL TEST FASE 1 — Portal Gereja

Tanggal: 2026-03-09
Branch: `fase1-tenant-isolation-rbac`
Environment: PHP 8.4.4, PHPUnit 11.5.55, sqlite `:memory:`

## Hasil final

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.4
Configuration: /home/anonimak/.hermes/workspace-main/portal-gereja/phpunit.xml

..........................................                        42 / 42 (100%)

Time: 00:01.666, Memory: 70.50 MB

OK (42 tests, 124 assertions)
```

**42 passed · 0 failed · 0 error · 0 risky**

## Perbaikan yang termasuk dalam fase ini

| Area | Perubahan | Temuan Vera |
|---|---|---|
| RBAC finance_admin | `TenantPolicy` default `allowedRoles = [super_admin, church_admin]`; `TransactionPolicy`/`FundPolicy`/`FinancialCategoryPolicy` override + finance_admin | BLOCK-1 (AC-T2-03) |
| Panel access | `User::canAccessPanel()` (hanya super_admin/church_admin/finance_admin); `AdminPanelProvider` perbaikan escape namespace discovery | BLOCK-2 (AC-T2-04) |
| Guard halaman laporan | `WartaJemaat::canAccess()` dan `LaporanRapatPage::canAccess()` | BLOCK-3 (AC-T2-06) |
| Super admin lintas gereja | `StatsOverview`, `CashFlowChart`, `WartaJemaat`, `SacramentsRelationManager` — tidak lagi filter `church_id` aktor; super_admin lihat semua gereja | HIGH-1 (AC-T1-04/09) |
| Cross-church FK | `BelongsToChurch::saving` + `churchForeignKeyMap()` pada Member/Event/EventRoster/MemberSacrament/Transaction/Official; non-super_admin yang memasang FK gereja lain → 403 | HIGH-2 |
| events.church_id NOT NULL | Migrasi `2026_03_09_000003_make_events_church_id_not_null.php` + backfill | MED (AC-T1-08) |

## Catatan environment test

- `RbacPageAccessTest` menggunakan `$this->withoutVite()` di `setUp()` karena environment test tidak punya `public/build/manifest.json` (tidak ada `npm run build`). Ini murni isu environment, bukan logika RBAC. Tanpa `withoutVite()`, render view `/admin` melempar "Vite manifest not found".
- Formatter `vendor/bin/pint --dirty` dijalankan (6 style issues di 22 file) — suite tetap hijau setelah format.
- Tidak ada test yang di-skip/disable.
