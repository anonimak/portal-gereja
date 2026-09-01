<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Template Topik Bimbingan default per gereja (A13 — dipanggil ChurchObserver):
 * Template Pra-Sidi (12 sesi) & Template Pra-Nikah (12 sesi), topik urut 1..12.
 */
class GuidanceTemplateSeeder extends Seeder
{
    private const PRA_SIDI_TOPICS = [
        'Pengenalan Iman Kristen & Alkitab',
        'Allah Tritunggal',
        'Manusia & Dosa',
        'Yesus Kristus & Keselamatan',
        'Roh Kudus & Kehidupan Baru',
        'Gereja & Persekutuan',
        'Ibadah & Doa',
        'Sakramen (Baptisan & Perjamuan Kudus)',
        'Kehidupan Kristen Sehari-hari',
        'Kesaksian & Pelayanan',
        'Etika & Moral Kristen',
        'Ikrar Sidi / Pengakuan Iman',
    ];

    private const PRA_NIKAH_TOPICS = [
        'Makna Pernikahan Kristen',
        'Dasar Alkitabiah Pernikahan',
        'Peran Suami & Istri',
        'Komunikasi dalam Keluarga',
        'Pengelolaan Keuangan Keluarga',
        'Konflik & Pengampunan',
        'Seksualitas & Keintiman',
        'Ibadah Keluarga & Iman',
        'Relasi dengan Mertua & Keluarga Besar',
        'Pola Asuh Anak',
        'Pelayanan & Komunitas',
        'Persiapan Pemberkatan Nikah',
    ];

    /**
     * Seed 2 template default untuk satu gereja.
     */
    public function run(int $churchId): void
    {
        $now = now()->toDateTimeString();

        $templates = [
            ['type' => 'pra_sidi', 'name' => 'Template Pra-Sidi Standar (12 sesi)', 'topics' => self::PRA_SIDI_TOPICS],
            ['type' => 'pra_nikah', 'name' => 'Template Pra-Nikah Standar (12 sesi)', 'topics' => self::PRA_NIKAH_TOPICS],
        ];

        foreach ($templates as $template) {
            $templateId = DB::table('guidance_templates')->insertGetId([
                'church_id' => $churchId,
                'type' => $template['type'],
                'name' => $template['name'],
                'session_count' => count($template['topics']),
                'is_default' => true,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($template['topics'] as $i => $topic) {
                DB::table('guidance_template_sessions')->insert([
                    'church_id' => $churchId,
                    'template_id' => $templateId,
                    'session_number' => $i + 1,
                    'topic' => $topic,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
