<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\LandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LandingSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_setting_default_initialization(): void
    {
        $setting = LandingSetting::current(null);

        $this->assertNotNull($setting);
        $this->assertNull($setting->church_id);
        $this->assertEquals('GKSBS Filadelfia', $setting->hero_title);
        $this->assertEquals('Roma 12:10', $setting->theme_verse_ref);
        $this->assertTrue($setting->is_active);
        $this->assertIsArray($setting->missions);
        $this->assertIsArray($setting->worship_schedules);
        $this->assertIsArray($setting->social_links);
    }

    public function test_landing_setting_caching_and_invalidation(): void
    {
        $setting = LandingSetting::current(null);
        $cacheKey = 'landing_setting_central';

        $this->assertTrue(Cache::has($cacheKey));

        $setting->update([
            'hero_title' => 'GKSBS Filadelfia Pusat Terpadu',
        ]);

        $this->assertFalse(Cache::has($cacheKey));

        $reloaded = LandingSetting::current(null);
        $this->assertEquals('GKSBS Filadelfia Pusat Terpadu', $reloaded->hero_title);
    }

    public function test_church_specific_landing_setting(): void
    {
        $church = Church::factory()->create(['name' => 'Pos Candimas']);
        $setting = LandingSetting::createDefault($church->id);

        $this->assertEquals($church->id, $setting->church_id);
        $retrieved = LandingSetting::getForChurch($church->id);
        $this->assertEquals($church->id, $retrieved->church_id);
    }

    public function test_welcome_page_renders_church_official_website_successfully(): void
    {
        Church::factory()->create([
            'code' => 'GKSBS-KEL-CDM',
            'name' => 'Jemaat Kelompok Candimas',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('GKSBS Filadelfia');
        $response->assertSee('Roma 12:10');
        $response->assertSee('Portal Jemaat');
        $response->assertSee('Area Majelis');
        $response->assertSee('Jemaat Kelompok Candimas');
        $response->assertSee('warta/GKSBS-KEL-CDM');
        $response->assertDontSee('Pintu Layanan');
        $response->assertDontSee('Fitur & Modul');
    }

    public function test_landing_setting_section_background_accessors(): void
    {
        $setting = LandingSetting::current(null);
        $setting->update([
            'warta_bg_path' => 'landing/parallax/custom-warta.jpg',
            'branches_bg_path' => 'landing/parallax/custom-branches.jpg',
        ]);

        $this->assertNotNull($setting->warta_bg_url);
        $this->assertStringContainsString('custom-warta.jpg', $setting->warta_bg_url);
        $this->assertNotNull($setting->branches_bg_url);
        $this->assertStringContainsString('custom-branches.jpg', $setting->branches_bg_url);
    }
}
