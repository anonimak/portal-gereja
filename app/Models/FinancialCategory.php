<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\AuditsActivity;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends Model
{
    use AuditsActivity, BelongsToChurch, HasFactory, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'name',
        'type',
    ];
}
