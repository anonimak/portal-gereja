<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use App\Models\WartaPublication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Publikasi Warta Jemaat — portal publik (task slot 16:00, backlog warta).
 *
 * Asumsi (tidak ada spec khusus):
 * 1. Publikasi = snapshot edisi Warta periode tertentu per gereja (status
 *    published + published_at <= now) yang tampil di halaman publik /warta.
 * 2. Halaman publik TANPA login; satu gereja per halaman (route by code);
 *    hanya menampilkan gereja yang dipilih (isolasi tenant).
 * 3. Publish dilakukan admin (super_admin / church_admin / warta_editor) via
 *    POST /admin/warta/publish; finance_admin & role lain ditolak (403).
 */
class WartaPublicationTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create(['code' => 'GKSBS-A']);
        $this->churchB = Church::factory()->create(['code' => 'GKSBS-B']);

        $this->adminA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);
    }

    private function publishedForA(): WartaPublication
    {
        return WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'title' => 'Warta Edisi A',
            'content' => [
                'church_name' => $this->churchA->name,
                'events' => [['name' => 'Ibadah Minggu', 'start' => '01/01/2026 08:00', 'location' => 'Gereja', 'officials' => 'Pdt. A']],
                'finance' => ['opening_balance' => 0, 'total_income' => 100000, 'total_expenses' => 40000, 'closing_balance' => 60000],
            ],
        ]);
    }

    // ---------- Model & scope ----------

    public function test_scope_published_hanya_menampilkan_edisi_live(): void
    {
        $live = $this->publishedForA();
        $draft = WartaPublication::factory()->draft()->create(['church_id' => $this->churchA->id]);
        $future = WartaPublication::factory()->scheduled(now()->addDay()->toDateTimeString())->create(['church_id' => $this->churchA->id]);

        $visible = WartaPublication::query()->published()->get();

        $this->assertTrue($visible->contains('id', $live->id));
        $this->assertFalse($visible->contains('id', $draft->id));
        $this->assertFalse($visible->contains('id', $future->id));
    }

    public function test_model_mengikuti_scope_church_saat_ada_aktor(): void
    {
        // church_admin A hanya melihat publikasi gereja A (global scope).
        $a = $this->publishedForA();
        WartaPublication::factory()->create(['church_id' => $this->churchB->id]);

        $this->actingAs($this->adminA);

        $visible = WartaPublication::query()->get();

        $this->assertTrue($visible->contains('id', $a->id));
        $this->assertCount(1, $visible);
    }

    // ---------- Portal publik (tanpa auth) ----------

    public function test_halaman_publik_index_menampilkan_edisi_gereja_terpilih(): void
    {
        $a = $this->publishedForA();
        WartaPublication::factory()->create(['church_id' => $this->churchB->id, 'title' => 'Warta B']);

        $response = $this->get(route('public.warta.index', ['church' => $this->churchA->code]));

        $response->assertOk();
        $response->assertSee('Warta Edisi A');
        $response->assertDontSee('Warta B');
    }

    public function test_halaman_publik_tidak_menampilkan_draft_atau_jadwal_mendatang(): void
    {
        WartaPublication::factory()->draft()->create(['church_id' => $this->churchA->id, 'title' => 'Draft Rahasia']);
        WartaPublication::factory()->scheduled()->create(['church_id' => $this->churchA->id, 'title' => 'Jadwal Mendatang']);

        $response = $this->get(route('public.warta.index', ['church' => $this->churchA->code]));

        $response->assertOk();
        $response->assertDontSee('Draft Rahasia');
        $response->assertDontSee('Jadwal Mendatang');
    }

    public function test_halaman_publik_show_menampilkan_konten_snapshot(): void
    {
        $a = $this->publishedForA();

        $response = $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $a->id]));

        $response->assertOk();
        $response->assertSee('Warta Edisi A');
        $response->assertSee('Ibadah Minggu');
        $response->assertSee('Rp 100.000');
    }

    public function test_halaman_publik_show_404_untuk_edisi_gereja_lain(): void
    {
        $b = WartaPublication::factory()->create(['church_id' => $this->churchB->id, 'title' => 'Warta B']);

        // Akses edisi gereja B lewat URL gereja A → 404 (tidak bocor).
        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $b->id]))->assertNotFound();

        // Edisi draft → 404.
        $draft = WartaPublication::factory()->draft()->create(['church_id' => $this->churchA->id]);
        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $draft->id]))->assertNotFound();
    }

    // ---------- Endpoint publish (admin) ----------

    public function test_church_admin_dapat_mempublikasikan_edisi(): void
    {
        $this->actingAs($this->adminA);

        $response = $this->post(route('warta.publish'), [
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('warta_publications', [
            'church_id' => $this->churchA->id,
            'status' => 'published',
        ]);
    }

    public function test_finance_admin_ditolak_publish(): void
    {
        $finance = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'finance_admin',
        ]);

        $this->actingAs($finance);

        $this->post(route('warta.publish'), [
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
        ])->assertForbidden();

        $this->assertDatabaseCount('warta_publications', 0);
    }

    public function test_church_admin_tidak_bisa_publish_untuk_gereja_lain(): void
    {
        $this->actingAs($this->adminA);

        // church_admin A mencoba publish utk gereja B → 403 (isolasi tenant).
        $this->post(route('warta.publish'), [
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'church_id' => $this->churchB->id,
        ])->assertForbidden();
    }

    public function test_unauthenticated_tidak_bisa_publish(): void
    {
        $this->post(route('warta.publish'), [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ])->assertRedirect(route('login'));
    }

    // ---------- Soft delete & audit ----------

    public function test_soft_delete_menyembunyikan_dari_portal(): void
    {
        $a = $this->publishedForA();
        $a->delete();

        $this->get(route('public.warta.index', ['church' => $this->churchA->code]))->assertDontSee('Warta Edisi A');
        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $a->id]))->assertNotFound();
    }

    public function test_soft_delete_dan_restore_model(): void
    {
        $a = $this->publishedForA();
        $a->delete();

        $this->assertSoftDeleted('warta_publications', ['id' => $a->id]);

        $a->restore();
        $this->assertNotSoftDeleted('warta_publications', ['id' => $a->id]);
    }

    public function test_publish_controller_menerima_dan_menyimpan_renungan_opsional(): void
    {
        $this->actingAs($this->adminA);

        $reflectionText = 'Segala perkara dapat kutanggung di dalam Dia yang memberi kekuatan kepadaku. (Filipi 4:13)';

        $response = $this->post(route('warta.publish'), [
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'reflection' => $reflectionText,
        ]);

        $response->assertRedirect();

        $publication = WartaPublication::where('church_id', $this->churchA->id)->latest('id')->first();
        $this->assertNotNull($publication);
        $this->assertSame($reflectionText, $publication->content['reflection'] ?? null);
    }

    public function test_warta_jemaat_livewire_page_publish_dan_load_reflection(): void
    {
        $this->actingAs($this->adminA);

        $reflectionText = 'Tuhan adalah gembalaku, takkan kekurangan aku. (Mazmur 23:1)';

        $page = new \App\Filament\Clusters\Reporting\Pages\WartaJemaat();
        $page->mount();
        $page->reflection = $reflectionText;
        $page->publishWarta();

        $pub = $page->getActivePublication();
        $this->assertNotNull($pub);
        $this->assertSame($this->churchA->id, $pub->church_id);
        $this->assertSame($reflectionText, $pub->content['reflection'] ?? null);

        $url = $page->getActivePublicationUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('/warta/' . $this->churchA->code . '/' . $pub->id, $url);

        // Mount ulang halaman baru pada periode yang sama: reflection harus otomatis termuat
        $page2 = new \App\Filament\Clusters\Reporting\Pages\WartaJemaat();
        $page2->mount();
        $this->assertSame($reflectionText, $page2->reflection);
    }

    public function test_warta_jemaat_publish_ditolak_untuk_role_tanpa_izin(): void
    {
        $finance = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'finance_admin',
        ]);

        $this->actingAs($finance);

        $page = new \App\Filament\Clusters\Reporting\Pages\WartaJemaat();
        $page->mount();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $page->publishWarta();
    }

    // ---------- Rollback / Tarik Publikasi ----------

    public function test_admin_dapat_rollback_warta_publikasi_via_endpoint(): void
    {
        $this->actingAs($this->adminA);

        $startDate = now()->startOfWeek()->toDateString();
        $endDate = now()->endOfWeek()->toDateString();

        $pub = WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'title' => 'Warta Siap Rollback',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->post(route('warta.rollback'), [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('warta_publications', [
            'id' => $pub->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Tidak lagi dapat diakses di portal publik
        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $pub->id]))
            ->assertNotFound();
        $this->get(route('public.warta.index', ['church' => $this->churchA->code]))
            ->assertDontSee('Warta Siap Rollback');
    }

    public function test_admin_dapat_rollback_warta_via_endpoint_json(): void
    {
        $this->actingAs($this->adminA);

        $startDate = now()->startOfWeek()->toDateString();
        $endDate = now()->endOfWeek()->toDateString();

        $pub = WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'title' => 'Warta JSON Rollback',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->postJson(route('warta.rollback'), [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Publikasi warta berhasil ditarik (rollback).',
            'publication' => [
                'id' => $pub->id,
                'status' => 'draft',
                'church_id' => $this->churchA->id,
            ],
        ]);

        $this->assertSoftDeleted('warta_publications', ['id' => $pub->id]);
    }

    public function test_role_tanpa_izin_ditolak_rollback_endpoint(): void
    {
        $finance = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'finance_admin',
        ]);

        $this->actingAs($finance);

        $this->post(route('warta.rollback'), [
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
        ])->assertForbidden();
    }

    public function test_unauthenticated_tidak_bisa_rollback_endpoint(): void
    {
        $this->post(route('warta.rollback'), [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ])->assertRedirect(route('login'));
    }

    public function test_church_admin_tidak_bisa_rollback_untuk_gereja_lain(): void
    {
        $this->actingAs($this->adminA);

        $this->post(route('warta.rollback'), [
            'start_date' => now()->startOfWeek()->toDateString(),
            'end_date' => now()->endOfWeek()->toDateString(),
            'church_id' => $this->churchB->id,
        ])->assertForbidden();
    }

    public function test_warta_jemaat_livewire_page_rollback(): void
    {
        $this->actingAs($this->adminA);

        $page = new \App\Filament\Clusters\Reporting\Pages\WartaJemaat();
        $page->mount();
        $page->reflection = 'Firman Hidup';
        $page->publishWarta();

        $pub = $page->getActivePublication();
        $this->assertNotNull($pub);
        $this->assertSame('published', $pub->status);

        // Lakukan rollback
        $page->rollbackWarta();

        // Publikasi aktif harus null, tapi teks renungan tetap tersimpan agar bisa diedit
        $this->assertNull($page->getActivePublication());
        $this->assertSame('Firman Hidup', $page->reflection);

        // Record di database sudah berstatus draft
        $this->assertDatabaseHas('warta_publications', [
            'id' => $pub->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Halaman publik 404
        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $pub->id]))
            ->assertNotFound();
    }

    public function test_warta_jemaat_rollback_ditolak_untuk_role_tanpa_izin(): void
    {
        $finance = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'finance_admin',
        ]);

        $this->actingAs($finance);

        $page = new \App\Filament\Clusters\Reporting\Pages\WartaJemaat();
        $page->mount();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $page->rollbackWarta();
    }

    public function test_alur_publish_rollback_dan_republish(): void
    {
        $this->actingAs($this->adminA);

        $page = new \App\Filament\Clusters\Reporting\Pages\WartaJemaat();
        $page->mount();
        $page->reflection = 'Versi 1';
        $page->publishWarta();

        $pub = $page->getActivePublication();
        $this->assertNotNull($pub);
        $pubId = $pub->id;

        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $pubId]))
            ->assertOk();

        // Rollback
        $page->rollbackWarta();
        $this->assertNull($page->getActivePublication());

        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $pubId]))
            ->assertNotFound();

        // Publikasikan kembali (re-publish)
        $page->reflection = 'Versi 2';
        $page->publishWarta();

        $repub = $page->getActivePublication();
        $this->assertNotNull($repub);
        $this->assertSame($pubId, $repub->id);
        $this->assertFalse($repub->trashed());
        $this->assertSame('published', $repub->status);
        $this->assertSame('Versi 2', $repub->content['reflection'] ?? null);

        $this->get(route('public.warta.show', ['church' => $this->churchA->code, 'publication' => $pubId]))
            ->assertOk();
    }
}
