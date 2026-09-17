<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class LandingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'hero_title',
        'hero_subtitle',
        'hero_tagline',
        'theme_verse',
        'theme_verse_ref',
        'hero_banner_path',
        'pastoral_greeting_title',
        'pastoral_greeting_author',
        'pastoral_greeting_author_role',
        'pastoral_greeting_photo_path',
        'pastoral_greeting_content',
        'about_summary',
        'vision',
        'missions',
        'worship_schedules',
        'contact_email',
        'contact_phone',
        'contact_address',
        'contact_maps_url',
        'social_links',
        'is_active',
    ];

    protected $casts = [
        'missions' => 'array',
        'worship_schedules' => 'array',
        'social_links' => 'array',
        'is_active' => 'boolean',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Ambil pengaturan landing page yang aktif (dengan auto-caching).
     */
    public static function current(?int $churchId = null): self
    {
        $cacheKey = 'landing_setting_'.($churchId ?? 'central');

        return Cache::rememberForever($cacheKey, function () use ($churchId) {
            $record = self::query()
                ->where('church_id', $churchId)
                ->where('is_active', true)
                ->first();

            if ($record) {
                return $record;
            }

            // Fallback default bila belum diisi
            return self::createDefault($churchId);
        });
    }

    /**
     * Helper alias untuk mengambil pengaturan per gereja / pusat.
     */
    public static function getForChurch(?int $churchId = null): self
    {
        return self::current($churchId);
    }

    /**
     * Bersihkan cache saat record diubah atau disimpan.
     */
    protected static function booted(): void
    {
        static::saved(function (self $setting): void {
            Cache::forget('landing_setting_'.($setting->church_id ?? 'central'));
        });

        static::deleted(function (self $setting): void {
            Cache::forget('landing_setting_'.($setting->church_id ?? 'central'));
        });
    }

    public function getHeroBannerUrlAttribute(): ?string
    {
        return $this->hero_banner_path ? Storage::disk('public')->url($this->hero_banner_path) : null;
    }

    public function getPastoralPhotoUrlAttribute(): ?string
    {
        return $this->pastoral_greeting_photo_path ? Storage::disk('public')->url($this->pastoral_greeting_photo_path) : null;
    }

    public static function createDefault(?int $churchId = null): self
    {
        return self::firstOrCreate(
            ['church_id' => $churchId],
            [
                'hero_title' => 'GKSBS Filadelfia',
                'hero_subtitle' => 'Gereja Kristen Sumatera Bagian Selatan — Klasis Tulang Bawang',
                'hero_tagline' => 'Gereja yang Terbuka, Oikumenis, dan Berakar dalam Kasih Kristus',
                'theme_verse' => 'Hendaklah kamu saling mengasihi sebagai saudara dan saling mendahului dalam memberi hormat.',
                'theme_verse_ref' => 'Roma 12:10',
                'pastoral_greeting_title' => 'Salam Kasih & Damai Sejahtera Kristus',
                'pastoral_greeting_author' => 'Pdt. Samuel Kriswanto, M.Th.',
                'pastoral_greeting_author_role' => 'Ketua Majelis Jemaat GKSBS Filadelfia',
                'pastoral_greeting_content' => "Selamat datang di Website Resmi GKSBS Filadelfia. Kami bersyukur atas kasih karunia Tuhan yang terus memelihara persekutuan jemaat induk dan seluruh jemaat kelompok (Candimas, Trimulyo, Margomulyo). Mari bersama kita terus berakar di dalam Kristus, bertumbuh dalam iman, dan berbuah lebat bagi masyarakat sekitar.",
                'about_summary' => "GKSBS Filadelfia adalah persekutuan jemaat yang berpusat pada Kristus, melayani warga jemaat di wilayah Lampung Tengah dan sekitarnya melalui jemaat induk dan pos-pos pelayanan kelompok jemaat secara terpadu dan guyub.",
                'vision' => 'Terwujudnya jemaat yang mandiri, misioner, berwawasan oikumenis, serta menjadi berkat nyata bagi sesama ciptaan Tuhan.',
                'missions' => [
                    'Menyelenggarakan peribadahan dan pembinaan rohani yang berakar pada Firman Allah.',
                    'Mempererat persaudaraan dan solidaritas antar jemaat kelompok secara guyub dan inklusif.',
                    'Mengembangkan karya pelayanan diakonia holistik bagi jemaat dan masyarakat luas.',
                    'Mewujudkan tata kelola organisasi dan pelayanan gereja yang transparan, akuntabel, dan tertib.',
                ],
                'worship_schedules' => [
                    ['name' => 'Ibadah Raya Minggu Induk', 'day' => 'Minggu', 'time' => '08:30 WIB', 'location' => 'Gedung Gereja Utama'],
                    ['name' => 'Kebaktian Anak / Sekolah Minggu', 'day' => 'Minggu', 'time' => '08:30 WIB', 'location' => 'Gedung Serbaguna'],
                    ['name' => 'Ibadah Pemuda & Remaja', 'day' => 'Sabtu', 'time' => '17:00 WIB', 'location' => 'Ruang Pemuda'],
                    ['name' => 'Persekutuan Doa & Pendalaman Alkitab', 'day' => 'Rabu', 'time' => '18:30 WIB', 'location' => 'Gedung Gereja'],
                ],
                'contact_email' => 'sekretariat@gksbs-filadelfia.org',
                'contact_phone' => '0812-7200-1234',
                'contact_address' => 'Jl. Gereja Filadelfia No. 01, Kel. Candimas, Kec. Natar, Lampung Selatan',
                'contact_maps_url' => 'https://maps.google.com/?q=GKSBS+Filadelfia',
                'social_links' => [
                    'youtube' => 'https://youtube.com/@gksbsfiladelfia',
                    'facebook' => 'https://facebook.com/gksbsfiladelfia',
                    'instagram' => 'https://instagram.com/gksbsfiladelfia',
                ],
                'is_active' => true,
            ]
        );
    }
}
