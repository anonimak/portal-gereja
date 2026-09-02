<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Edisi Warta Jemaat yang dipublikasikan ke portal publik.
 *
 * Snapshot konten per periode (JSON), di-scope per gereja (BelongsToChurch).
 * Halaman publik hanya menampilkan status=published & published_at <= now.
 */
class WartaPublication extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'title',
        'period_start',
        'period_end',
        'content',
        'status',
        'published_at',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'content' => 'array',
        'published_at' => 'datetime',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Hanya edisi yang benar-benar live di portal publik.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Edisi milik satu gereja (dipakai halaman publik — tanpa auth).
     */
    public function scopeForChurch(Builder $query, int $churchId): Builder
    {
        return $query->where('church_id', $churchId);
    }

    /**
     * Judul periode utk keterbacaan (mis. "Edisi 14–20 September 2026").
     */
    public function periodLabel(): string
    {
        if ($this->period_start && $this->period_end) {
            return $this->period_start->format('d M Y').' – '.$this->period_end->format('d M Y');
        }

        return $this->title;
    }
}
