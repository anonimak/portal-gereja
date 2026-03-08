<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinistryRole extends Model
{
    use HasFactory, BelongsToChurch;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];
}
