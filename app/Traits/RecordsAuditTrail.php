<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait audit trail (Fase 2): mencatat created/updated/deleted/restored/force_deleted
 * ke tabel audit_logs.
 *
 * - Append-only, satu baris per aksi (tanpa N+1 tambahan selain 1 INSERT).
 * - Hanya kolom BERUBAH yang dicatat untuk update (getDirty + getOriginal intersect).
 * - Kolom sensitif (password, remember_token) SELALU di-redact (C1 Vera / AC-AU-03).
 * - Hanya atribut fillable + id + church_id yang direkam (M4 Vera).
 * - Audit membawa church_id aktor/record untuk isolasi tenant (H1 Vera / AC-TN).
 * - Soft delete & restore ditangani event deleted/restored; event updated yang
 *   hanya mengubah deleted_at dilewati agar tidak dobel-log.
 * - forceDelete memicu deleted + force_deleted → guard isForceDeleting() agar
 *   hanya satu baris (M1 Vera).
 * - Catatan Laravel 12: helper static::restored()/forceDeleted() sudah tidak ada
 *   di HasEvents; registrasi via registerModelEvent() (protected static).
 */
trait RecordsAuditTrail
{
    /**
     * Boot the trait: register model events untuk mencatat perubahan.
     */
    public static function bootRecordsAuditTrail(): void
    {
        static::created(function (Model $model): void {
            $model->recordAudit('created', null, $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            // Soft delete/restore ditangani event deleted/restored — hindari log ganda.
            if ($model->isDirty('deleted_at')) {
                return;
            }

            $dirty = $model->getDirty();
            $old = array_intersect_key($model->getOriginal(), $dirty);

            $model->recordAudit('updated', $old, $dirty);
        });

        static::deleted(function (Model $model): void {
            // forceDelete memicu deleted + force_deleted → hanya catat via force_deleted.
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $model->recordAudit('deleted', $model->getAttributes(), null);
        });

        static::registerModelEvent('restored', function (Model $model): void {
            $model->recordAudit('restored', null, $model->getAttributes());
        });

        static::registerModelEvent('forceDeleted', function (Model $model): void {
            $model->recordAudit('force_deleted', $model->getAttributes(), null);
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
        $oldValues = $oldValues === null ? null : $this->sanitizeAuditValues($oldValues);
        $newValues = $newValues === null ? null : $this->sanitizeAuditValues($newValues);

        // Tidak ada data berarti (mis. hanya remember_token berubah) → lewati (L1).
        if ($action === 'updated' && $newValues === []) {
            return;
        }

        $actor = auth()->user();

        AuditLog::create([
            'user_id' => $actor?->id,
            'church_id' => $this->church_id ?? $actor?->church_id ?? null,
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 255) ?: null,
        ]);
    }

    /**
     * Kolom yang tidak boleh masuk payload audit.
     *
     * @return array<int, string>
     */
    protected function auditSensitiveFields(): array
    {
        return ['password', 'remember_token'];
    }

    /**
     * Saring nilai audit: hanya atribut fillable + id + church_id,
     * dan selalu kecualikan kolom sensitif.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function sanitizeAuditValues(array $values): array
    {
        $allowed = array_flip($this->getFillable());
        $allowed['id'] = true;
        $allowed['church_id'] = true;

        $excluded = array_flip($this->auditSensitiveFields());

        $result = [];
        foreach ($values as $key => $value) {
            if (isset($excluded[$key])) {
                continue;
            }

            if (! isset($allowed[$key])) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
