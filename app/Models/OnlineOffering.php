<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToChurch;
use App\Traits\RecordsAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnlineOffering extends Model
{
    use BelongsToChurch, HasFactory, RecordsAuditTrail, SoftDeletes;

    /**
     * Map FK yang wajib dalam satu tenant gereja.
     *
     * @return array<string, class-string<Model>>
     */
    protected function churchForeignKeyMap(): array
    {
        return [
            'fund_id' => Fund::class,
            'financial_category_id' => FinancialCategory::class,
        ];
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'church_id',
        'member_id',
        'fund_id',
        'financial_category_id',
        'donor_name',
        'donor_phone',
        'donor_email',
        'amount',
        'payment_method',
        'bank_name',
        'reference_code',
        'proof_path',
        'prayer_notes',
        'status',
        'confirmed_by',
        'confirmed_at',
        'transaction_id',
        'rejection_reason',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Generate reference code unik (OFF-YYYYMMDD-XXXXX).
     */
    public static function generateReferenceCode(): string
    {
        do {
            $code = 'OFF-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (static::where('reference_code', $code)->exists());

        return $code;
    }

    /**
     * Relasi ke Anggota Jemaat (jika login).
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relasi ke Pos Dana/Kantong Kas.
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * Relasi ke Kategori Keuangan.
     */
    public function financialCategory(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'financial_category_id');
    }

    /**
     * Relasi ke Petugas yang Mengonfirmasi.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Relasi ke Transaksi Pembukuan Kas (setelah dikonfirmasi).
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * URL Bukti Transfer.
     */
    public function getProofUrlAttribute(): ?string
    {
        if (! $this->proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->proof_path);
    }

    /**
     * Konfirmasi persembahan dan otomatis buat baris Transaction pembukuan kas.
     */
    public function confirm(User $actor): Transaction
    {
        if ($this->status === 'confirmed' && $this->transaction_id) {
            return $this->transaction;
        }

        // Buat baris Transaction penerimaan kas tunai/bank
        $transaction = Transaction::create([
            'church_id' => $this->church_id,
            'fund_id' => $this->fund_id,
            'category_id' => $this->financial_category_id,
            'type' => 'debit',
            'amount' => $this->amount,
            'transaction_date' => Carbon::now()->toDateString(),
            'description' => "Persembahan Online [{$this->reference_code}] dari {$this->donor_name}",
        ]);

        $this->update([
            'status' => 'confirmed',
            'confirmed_by' => $actor->id,
            'confirmed_at' => Carbon::now(),
            'transaction_id' => $transaction->id,
            'rejection_reason' => null,
        ]);

        return $transaction;
    }

    /**
     * Tolak persembahan.
     */
    public function reject(User $actor, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'confirmed_by' => $actor->id,
            'rejection_reason' => $reason,
        ]);
    }
}
