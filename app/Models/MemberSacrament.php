<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSacrament extends Model
{
    use BelongsToChurch, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'type',
        'sacrament_date',
        'official_id',
        'certificate_number',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sacrament_date' => 'date',
    ];

    /**
     * Member that this sacrament record belongs to.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Official that ministered this sacrament.
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }
}
