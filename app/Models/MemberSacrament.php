<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSacrament extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'sacrament_date',
        'minister_name',
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
}
