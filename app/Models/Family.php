<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'family_number',
        'name',
        'address',
    ];

    /**
     * Members that belong to this family.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
