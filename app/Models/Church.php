<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

final class Church extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
    ];

    /**
     * Users that belong to the church.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
