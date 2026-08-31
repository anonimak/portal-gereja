<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Trait audit trail (Fase 2): mencatat create/update/delete/restore/forceDelete
 * ke tabel audit_logs.
 *
 * - Append-only, satu baris per aksi (tanpa N+1 tambahan selain 1 INSERT).
 * - Aman untuk mass-assignment: AuditLog punya $fillable lengkap.
 * - Tidak ada rekursi: model AuditLog sendiri tidak memakai trait ini.
 * - Soft delete & restore ditangani event deleted/restored; event updated yang
 *   hanya mengubah deleted_at dilewati agar tidak dobel-log.
 * - Catatan Laravel 12: helper static::restored()/forceDeleted() sudah tidak
 *   ada di HasEvents; registrasi via registerModelEvent() (protected static,
 *   bisa dipanggil dari dalam trait yang di-compose ke model).
 */
trait AuditsActivity
{
    /**
     * Boot the trait: register model events untuk mencatat perubahan.
     */
    public static function bootAuditsActivity(): void
    {
        static::created(function ($model): void {
            $model->recordAudit('create', null, $model->getAttributes());
        });

        static::updated(function ($model): void {
            // Soft delete/restore ditangani event deleted/restored — hindari log ganda.
            if ($model->isDirty('deleted_at')) {
                return;
            }

            $model->recordAudit('update', $model->getOriginal(), $model->getAttributes());
        });

        static::deleted(function ($model): void {
            $model->recordAudit('delete', $model->getAttributes(), null);
        });

        static::registerModelEvent('restored', function ($model): void {
            $model->recordAudit('restore', null, $model->getAttributes());
        });

        static::registerModelEvent('forceDeleted', function ($model): void {
            $model->recordAudit('forceDelete', $model->getAttributes(), null);
        });
    }

    /**
     * Simpan satu baris audit untuk aksi pada model.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    protected function recordAudit(string $action, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip' => request()->ip(),
        ]);
    }
}
