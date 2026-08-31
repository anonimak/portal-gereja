<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Church;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToChurch
{
    /**
     * Relationship to Church.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Daftar kolom foreign key yang WAJIB satu gereja dengan record ini.
     *
     * Format: ['family_id' => Family::class, 'member_id' => Member::class, ...]
     * Default kosong — model yang punya FK silang harus meng-override method ini.
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [];
    }

    /**
     * Turunkan church_id dari FK induk ketika church_id belum terisi.
     *
     * Dipakai untuk skenario super_admin yang menambah record pada gereja lain
     * (mis. sakramen untuk member gereja lain via RelationManager): church_id
     * harus mengikuti gereja induk, bukan gereja aktor.
     *
     * Default null — model yang punya FK induk harus meng-override method ini.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        return null;
    }

    /**
     * Boot the trait: add global scope, creating event, and FK consistency guard.
     */
    public static function bootBelongsToChurch(): void
    {
        static::addGlobalScope('church', function (Builder $builder) {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            // super_admin: SELALU melihat semua gereja pada global scope.
            // Pemilih gereja super_admin (§9) TIDAK mengubah global scope —
            // hanya diterapkan eksplisit oleh halaman laporan via
            // HasChurchScope::scopeToActiveChurch() (Vera: LOW — pastikan
            // hanya untuk laporan, bukan semua resource/CRUD).
            if ($user->role === 'super_admin') {
                return;
            }

            $builder->where('church_id', $user->church_id);
        });

        static::creating(function ($model) {
            $actor = auth()->user();

            // 1. Non-super_admin DIPAKSA menulis ke gereja sendiri — menutup celah
            //    mass-assignment yang mengarahkan church_id ke gereja lain.
            if ($actor && $actor->role !== 'super_admin') {
                $model->church_id = $actor->church_id;
            }

            // 2. Kalau masih kosong, turunkan dari FK induk (member/event/family/dst).
            //    Penting untuk super_admin yang menambah data pada gereja lain.
            if (empty($model->church_id)) {
                $derived = $model->deriveChurchIdFromParent();
                if ($derived !== null) {
                    $model->church_id = $derived;
                }
            }

            // 3. Fallback terakhir: gereja aktor.
            if (empty($model->church_id) && $actor) {
                $model->church_id = $actor->church_id;
            }
        });

        // HIGH-2 Vera: tolak data dengan FK milik gereja lain (cross-church).
        static::saving(function ($model) {
            $model->assertChurchForeignKeysConsistent();
        });
    }

    /**
     * Validasi server-side bahwa semua FK bertanda gereja (church-scoped FK)
     * menunjuk ke record pada gereja yang sama dengan record ini.
     *
     * Aturan:
     * - Tanpa aktor terautentikasi (console/seeder/factory): dilewati.
     * - Non-super_admin: target gereja = gereja aktor (karena creating memaksa
     *   church_id ke gereja aktor). Member dengan family_id gereja lain => 403.
     * - Super_admin: target = church_id record bila terisi; bila null dilewati
     *   (flow relation-manager/form mengisi church_id secara implisit).
     */
    protected function assertChurchForeignKeysConsistent(): void
    {
        $actor = auth()->user();
        if (! $actor) {
            return;
        }

        $map = $this->churchForeignKeyMap();
        if ($map === []) {
            return;
        }

        $targetChurchId = $actor->role === 'super_admin'
            ? $this->church_id
            : $actor->church_id;

        if ($targetChurchId === null) {
            return;
        }

        foreach ($map as $column => $relatedModel) {
            $value = $this->{$column};
            if ($value === null || $value === '') {
                continue;
            }

            $related = $relatedModel::query()
                ->withoutGlobalScopes()
                ->find($value);

            if ($related && (int) $related->church_id !== (int) $targetChurchId) {
                abort(403, "Data referensi '{$column}' milik gereja lain tidak diizinkan.");
            }
        }
    }
}
