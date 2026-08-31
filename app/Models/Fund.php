<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'name',
    ];
}
