<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'synod',
        'address',
        'phone',
        'email',
        'logo_path',
        'website',
    ];

    /**
     * Users that belong to the church.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Edisi Warta yang dipublikasikan gereja ini (portal publik).
     */
    public function wartaPublications(): HasMany
    {
        return $this->hasMany(WartaPublication::class);
    }

    /**
     * URL logo untuk ditampilkan pada halaman web / portal.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Path fisik absolut logo untuk dibaca Dompdf secara lokal.
     */
    public function getLogoRealPathAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $path = Storage::disk('public')->path($this->logo_path);

        return file_exists($path) ? $path : null;
    }

    /**
     * Data URI Base64 logo untuk Dompdf (paling aman dari batasan path & isRemoteEnabled).
     */
    public function getLogoBase64Attribute(): ?string
    {
        $realPath = $this->logo_real_path;
        if (! $realPath) {
            return null;
        }

        try {
            $mime = mime_content_type($realPath) ?: 'image/png';
            $data = base64_encode(file_get_contents($realPath));

            return "data:{$mime};base64,{$data}";
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Gabungan telepon, email, website untuk kop surat.
     */
    public function getFormattedContactAttribute(): string
    {
        $parts = [];
        if (! empty($this->phone)) {
            $parts[] = 'Telp: ' . $this->phone;
        }
        if (! empty($this->email)) {
            $parts[] = 'Email: ' . $this->email;
        }
        if (! empty($this->website)) {
            $parts[] = 'Web: ' . $this->website;
        }

        return implode(' • ', $parts);
    }
}
