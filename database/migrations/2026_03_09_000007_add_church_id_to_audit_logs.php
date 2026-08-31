<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * H1 Vera + AC-TN-01/02/03: audit_logs harus terisolasi per gereja.
 * 1. Tambah kolom church_id nullable + FK nullOnDelete + index.
 * 2. Backfill baris lama: church_id dari record auditable (bila punya church_id),
 *    fallback dari users.church_id (untuk log audit User).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('church_id')->nullable()->after('user_id')->constrained('churches')->nullOnDelete();
            $table->index('church_id');
        });

        $logs = DB::table('audit_logs')->whereNull('church_id')->get(['id', 'user_id', 'auditable_type', 'auditable_id']);

        foreach ($logs as $log) {
            $churchId = $this->resolveChurchId($log);
            if ($churchId !== null) {
                DB::table('audit_logs')->where('id', $log->id)->update(['church_id' => $churchId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('church_id');
        });
    }

    /**
     * @param  object{user_id: int|null, auditable_type: string|null, auditable_id: int|null}  $log
     */
    private function resolveChurchId(object $log): ?int
    {
        $auditableType = $log->auditable_type;

        if ($auditableType && class_exists($auditableType)) {
            try {
                $model = method_exists($auditableType, 'withTrashed')
                    ? $auditableType::withTrashed()->withoutGlobalScopes()->find($log->auditable_id)
                    : $auditableType::query()->withoutGlobalScopes()->find($log->auditable_id);

                if ($model && in_array('church_id', $model->getFillable(), true) && $model->church_id !== null) {
                    return (int) $model->church_id;
                }
            } catch (Throwable $e) {
                // abaikan — fallback ke user.
            }
        }

        if ($log->user_id) {
            $userChurchId = DB::table('users')->where('id', $log->user_id)->value('church_id');
            if ($userChurchId !== null) {
                return (int) $userChurchId;
            }
        }

        return null;
    }
};
