<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_settings', function (Blueprint $table): void {
            $table->id();
            // church_id nullable: NULL = Pengaturan Utama Gereja Induk / Pusat.
            // Jika gereja cabang memiliki custom domain/landing page di kemudian hari, dapat diisi church_id.
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->index('church_id');

            // 1. Hero & Tema Tahunan
            $table->string('hero_title')->default('GKSBS Filadelfia');
            $table->string('hero_subtitle')->nullable()->default('Gereja Kristen Sumatera Bagian Selatan');
            $table->string('hero_tagline')->default('Bertumbuh dalam Iman, Berakar dalam Kasih, Berbuah bagi Sesama');
            $table->text('theme_verse')->comment('Teks lengkap ayat tema tahunan beserta referensi (misal: Roma 12:10)');
            $table->string('theme_verse_ref')->nullable()->default('Roma 12:10');
            $table->string('hero_banner_path')->nullable()->comment('Path gambar banner hero di storage public');

            // 2. Sambutan Gembala / Majelis Jemaat
            $table->string('pastoral_greeting_title')->default('Salam Kasih dari Majelis Jemaat');
            $table->string('pastoral_greeting_author')->default('Pdt. Samuel Kriswanto, M.Th.');
            $table->string('pastoral_greeting_author_role')->default('Ketua Majelis Jemaat Induk');
            $table->string('pastoral_greeting_photo_path')->nullable();
            $table->longText('pastoral_greeting_content');

            // 3. Profil, Visi, Misi
            $table->longText('about_summary')->comment('Ringkasan sejarah dan profil gereja');
            $table->text('vision')->comment('Visi gereja');
            $table->json('missions')->nullable()->comment('Array daftar butir-butir misi');

            // 4. Jadwal Ibadah Induk & Kontak
            $table->json('worship_schedules')->nullable()->comment('Array terstruktur jadwal ibadah rutin induk');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('contact_address')->nullable();
            $table->text('contact_maps_url')->nullable()->comment('URL Google Maps atau link embed');
            $table->json('social_links')->nullable()->comment('JSON key-value: facebook, youtube, instagram, whatsapp');

            // 5. Kontrol Publikasi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_settings');
    }
};
