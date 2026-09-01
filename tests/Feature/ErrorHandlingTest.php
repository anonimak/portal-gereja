<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Monolog\Formatter\JsonFormatter;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/e2-test-throw', fn () => throw new \RuntimeException('e2-test-boom-detail'));
        Route::get('/e2-test-forbidden', fn () => abort(403));
    }

    public function test_404_memakai_halaman_error_kustom_tanpa_bocor_detail(): void
    {
        $response = $this->get('/e2-halaman-tidak-ada-xyz');

        $response->assertStatus(404);
        $response->assertSee('Halaman Tidak Ditemukan');
        $response->assertDontSee('NotFoundHttpException');
        $response->assertDontSee('stack trace');
    }

    public function test_500_tidak_membocorkan_exception_mentah_ke_user(): void
    {
        config(['app.debug' => false]);

        $response = $this->get('/e2-test-throw');

        $response->assertStatus(500);
        $response->assertSee('Terjadi Kesalahan');
        // Detail exception & lokasi file TIDAK boleh muncul di response.
        $response->assertDontSee('e2-test-boom-detail');
        $response->assertDontSee('RuntimeException');
    }

    public function test_500_json_tidak_membocorkan_detail(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/e2-test-throw');

        $response->assertStatus(500);
        $this->assertIsString($response->json('message'));
        $this->assertStringNotContainsString('e2-test-boom-detail', (string) $response->json('message'));
    }

    public function test_403_memakai_halaman_error_kustom(): void
    {
        $response = $this->get('/e2-test-forbidden');

        $response->assertStatus(403);
        $response->assertSee('Akses Ditolak');
        $response->assertDontSee('HttpException');
    }

    public function test_503_memakai_halaman_error_kustom(): void
    {
        try {
            $this->artisan('down', ['--retry' => 60])->assertExitCode(0);
            $response = $this->get('/');
            $response->assertStatus(503);
            $response->assertSee('Sedang Pemeliharaan');
        } finally {
            $this->artisan('up');
        }
    }

    public function test_channel_logging_terstruktur_tersedia(): void
    {
        $this->assertSame('daily', config('logging.channels.structured.driver'));
        $this->assertSame(JsonFormatter::class, config('logging.channels.structured.formatter'));
        $this->assertTrue(config('logging.channels.structured.formatter_with.includeStacktraces'));
    }
}
