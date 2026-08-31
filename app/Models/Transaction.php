<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use BelongsToChurch, HasFactory;

    /**
     * Kolom FK yang harus satu gereja dengan transaksi ini (HIGH-2 Vera).
     *
     * @return array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'fund_id' => Fund::class,
            'category_id' => FinancialCategory::class,
        ];
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'fund_id',
        'category_id',
        'type',
        'amount',
        'transaction_date',
        'description',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'transaction_date' => 'date',
    ];

    /**
     * Fund that this transaction belongs to.
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * Financial category for this transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class);
    }
}
