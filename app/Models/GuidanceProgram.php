<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Program Bimbingan (Pra-Sidi / Pra-Nikah) — payung sesi.
 * Dibuat dari template (sesi auto-install 1..N) atau manual tanpa template.
 */
class GuidanceProgram extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * FK yang harus satu gereja dengan program (template_id bila dipakai).
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return ['template_id' => GuidanceTemplate::class];
    }

    /**
     * Church_id mengikuti gereja template bila program dibuat dari template.
     */
    protected function deriveChurchIdFromParent(): ?int
    {
        if (! $this->template_id) {
            return null;
        }

        return GuidanceTemplate::query()
            ->withoutGlobalScopes()
            ->whereKey($this->template_id)
            ->value('church_id');
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'type',
        'title',
        'start_date',
        'end_date',
        'status',
        'template_id',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Pertemuan-pertemuan program ini (diurutkan jadwal).
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(GuidanceSession::class, 'program_id')
            ->orderBy('session_at')
            ->orderBy('id');
    }

    /**
     * Template sumber (informasional — topik disalin saat instantiate).
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(GuidanceTemplate::class, 'template_id');
    }

    /**
     * Instantiate sesi 1..N dari template sumber (AC-LC-18).
     *
     * - Membuat GuidanceSession per baris guidance_template_sessions dengan
     *   title = topik template, session_at/official_id null (disesuaikan manual).
     * - Template sumber TIDAK diubah (A14 — topik disalin, bukan relasi hidup).
     * - Aman dipanggil ulang: sesi yang sudah ada tidak diduplikasi (skip jika
     *   title sama sudah ada di program ini).
     *
     * @return int jumlah sesi yang dibuat baru
     */
    public function instantiateFromTemplate(): int
    {
        if (! $this->template_id) {
            return 0;
        }

        $template = GuidanceTemplate::query()
            ->withoutGlobalScopes()
            ->with(['sessions' => fn ($q) => $q->withoutGlobalScopes()])
            ->find($this->template_id);

        if (! $template) {
            return 0;
        }

        $existingTitles = $this->sessions()
            ->withoutGlobalScopes()
            ->pluck('title')
            ->map(fn (?string $t) => trim((string) $t))
            ->filter()
            ->all();

        $created = 0;
        foreach ($template->sessions as $templateSession) {
            $title = trim((string) $templateSession->topic);
            if (in_array($title, $existingTitles, true)) {
                continue;
            }

            $this->sessions()->create([
                'church_id' => $this->church_id,
                'title' => $title,
                'session_at' => null,
                'location' => null,
                'official_id' => null,
                'notes' => null,
            ]);
            $created++;
        }

        return $created;
    }
}
