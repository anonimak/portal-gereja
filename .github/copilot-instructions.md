# AGENT INSTRUCTION: Multi-Tenant Church Management System — Condensed

## Goal

Fast, maintainable TALL-stack ChMS with single-database, column-tenancy using `church_id`.

## Stack (required)

- PHP >= 8.4, Laravel 12.x, Filament 5.x, Livewire 4.x, Tailwind/Alpine (latest), MariaDB (InnoDB)

## Tenancy & Models

- Single DB, `church_id` column on all transactional models (Family, Member, Transaction, Event).
- Implement `BelongsToChurch` trait:
    - Adds a Global Scope: filter by `auth()->user()->church_id` unless user.role === 'super_admin'.
    - On creating: set `church_id` from authenticated user.
- All tenant models must use the trait.

## Coding Standards

- declare(strict_types=1); use strict typing, property & return types.
- Use Laravel 12 conventions and casts (e.g., AsArrayObject for JSON).
- Migrations: explicit FKs (`constrained()->cascadeOnDelete()`), indexes on `church_id`, `family_id`; use `bigInteger` for money.

## Filament & UI

- Prefer native Filament Resources, RelationManagers, Actions over custom Livewire.
- Filament tables: always `with()` for relations to avoid N+1; use pagination/chunking (never Model::all()).
- Client-side: Alpine.js only. No heavy JS libs.

## Performance Constraints

- Target server: 1GB RAM, 2 vCPUs.
- Avoid heavy queues; prefer synchronous/lightweight DB queues.
- Avoid memory-heavy operations; paginate/chunk and eager-load relations.

## Execution (when implementing a feature)

1. Provide Migration (with indexes & FKs).
2. Provide Model (relations, fillable/guarded, casts, use BelongsToChurch).
3. Provide Filament Resource (Form, Table, RelationManagers if needed).
4. Use Filament policies/RBAC (role stored on users.role). Keep logic minimal.

## Deliverables

- Production-ready code only; minimal comments. Prefer built-in features.
