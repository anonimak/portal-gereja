<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRoster;
use App\Models\Family;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\MinistryRole;
use App\Models\Official;
use App\Models\User;
use App\Models\WartaPublication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;

    private Church $churchB;

    private Family $familyA;

    private Member $memberA1;

    private Member $memberA2;

    private Member $memberB;

    private User $userA1;

    private User $userA2;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create(['name' => 'Gereja A', 'code' => 'gereja-a']);
        $this->churchB = Church::factory()->create(['name' => 'Gereja B', 'code' => 'gereja-b']);

        // Keluarga di Gereja A
        $this->familyA = Family::factory()->create([
            'church_id' => $this->churchA->id,
            'family_number' => 'KK-001-A',
            'name' => 'Keluarga Santoso',
            'address' => 'Jl. Kebon Jeruk No. 12',
        ]);

        // Member A1 (Kepala Keluarga)
        $this->memberA1 = Member::factory()->create([
            'church_id' => $this->churchA->id,
            'family_id' => $this->familyA->id,
            'id_card_number' => '3171011111110001',
            'full_name' => 'Santoso Surya',
            'gender' => 'm',
            'birth_place' => 'Jakarta',
            'birth_date' => '1985-05-10',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
        ]);

        // Member A2 (Istri di keluarga sama)
        $this->memberA2 = Member::factory()->create([
            'church_id' => $this->churchA->id,
            'family_id' => $this->familyA->id,
            'id_card_number' => '3171011111110002',
            'full_name' => 'Maria Surya',
            'gender' => 'f',
            'birth_place' => 'Bandung',
            'birth_date' => '1988-08-15',
            'family_relation' => 'istri',
            'status' => 'aktif',
        ]);

        // Member B (Gereja B)
        $this->memberB = Member::factory()->create([
            'church_id' => $this->churchB->id,
            'id_card_number' => '3172022222220001',
            'full_name' => 'Budi Gereja B',
            'status' => 'aktif',
        ]);

        // User A1
        $this->userA1 = User::factory()->create([
            'church_id' => $this->churchA->id,
            'member_id' => $this->memberA1->id,
            'name' => 'Santoso Surya',
            'email' => 'santoso@gereja-a.test',
            'password' => Hash::make('password123'),
            'role' => 'member',
        ]);

        // User A2
        $this->userA2 = User::factory()->create([
            'church_id' => $this->churchA->id,
            'member_id' => $this->memberA2->id,
            'name' => 'Maria Surya',
            'email' => 'maria@gereja-a.test',
            'password' => Hash::make('password123'),
            'role' => 'member',
        ]);

        // User B
        $this->userB = User::factory()->create([
            'church_id' => $this->churchB->id,
            'member_id' => $this->memberB->id,
            'name' => 'Budi Gereja B',
            'email' => 'budi@gereja-b.test',
            'password' => Hash::make('password123'),
            'role' => 'member',
        ]);
    }

    // ==========================================
    // 1. AUTENTIKASI & LOGIN
    // ==========================================

    public function test_api_login_dengan_email_berhasil(): void
    {
        $response = $this->postJson('/api/portal/login', [
            'login' => 'santoso@gereja-a.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'id' => $this->userA1->id,
                    'email' => 'santoso@gereja-a.test',
                    'member_id' => $this->memberA1->id,
                ],
                'member' => [
                    'id' => $this->memberA1->id,
                    'full_name' => 'Santoso Surya',
                    'id_card_number' => '3171011111110001',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertNotNull($this->userA1->fresh()->api_token);
    }

    public function test_api_login_dengan_nomor_identitas_ktp_berhasil(): void
    {
        $response = $this->postJson('/api/portal/login', [
            'login' => '3171011111110001', // No. KTP
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'id' => $this->userA1->id,
                    'email' => 'santoso@gereja-a.test',
                    'member_id' => $this->memberA1->id,
                ],
                'member' => [
                    'id' => $this->memberA1->id,
                    'full_name' => 'Santoso Surya',
                ],
            ]);
    }

    public function test_api_login_kredensial_salah_ditolak(): void
    {
        $response = $this->postJson('/api/portal/login', [
            'login' => 'santoso@gereja-a.test',
            'password' => 'password-salah',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Kredensial tidak valid.',
            ]);
    }

    public function test_web_login_dan_logout_berhasil(): void
    {
        // Login Web pakai email
        $response = $this->post('/portal/login', [
            'login' => 'santoso@gereja-a.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/portal/profile');
        $this->assertAuthenticatedAs($this->userA1);

        // Logout
        $logout = $this->post('/portal/logout');
        $logout->assertRedirect('/portal/login');
        $this->assertGuest();

        // Login Web pakai No. KTP
        $responseKtp = $this->post('/portal/login', [
            'login' => '3171011111110001',
            'password' => 'password123',
        ]);

        $responseKtp->assertRedirect('/portal/profile');
        $this->assertAuthenticatedAs($this->userA1);
    }

    public function test_api_portal_menolak_unauthenticated(): void
    {
        $this->getJson('/api/portal/profile')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->getJson('/api/portal/events')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->getJson('/api/portal/warta')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_web_portal_redirect_ke_login_jika_unauthenticated(): void
    {
        $this->get('/portal/profile')
            ->assertRedirect(route('portal.login'));

        $this->get('/portal/events')
            ->assertRedirect(route('portal.login'));

        $this->get('/portal/warta')
            ->assertRedirect(route('portal.login'));
    }

    public function test_api_portal_dengan_bearer_token(): void
    {
        $token = 'test-token-bearer-1234567890';
        $this->userA1->update(['api_token' => $token]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/portal/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $this->userA1->id,
                        'email' => $this->userA1->email,
                    ],
                ],
            ]);
    }

    public function test_api_logout_menghapus_token(): void
    {
        $token = 'token-to-revoke';
        $this->userA1->update(['api_token' => $token]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/portal/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Berhasil logout.']);

        $this->assertNull($this->userA1->fresh()->api_token);
    }

    // ==========================================
    // 2. DATA DIRI, KELUARGA, DAN SAKRAMEN
    // ==========================================

    public function test_api_profile_mengembalikan_data_lengkap(): void
    {
        // Buat data sakramen untuk member A1
        $official = Official::factory()->create([
            'church_id' => $this->churchA->id,
            'external_name' => 'Pdt. Johanes',
        ]);

        MemberSacrament::factory()->create([
            'church_id' => $this->churchA->id,
            'member_id' => $this->memberA1->id,
            'type' => 'sidi',
            'sacrament_date' => '2005-04-10',
            'certificate_number' => 'SIDI/2005/012',
            'official_id' => $official->id,
        ]);

        $response = $this->actingAs($this->userA1)->getJson('/api/portal/profile');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $this->memberA1->id,
                    'full_name' => 'Santoso Surya',
                    'id_card_number' => '3171011111110001',
                    'gender' => 'm',
                    'gender_label' => 'Laki-laki',
                    'family_relation' => 'kepala_keluarga',
                    'family_relation_label' => 'Kepala Keluarga',
                    'church' => [
                        'id' => $this->churchA->id,
                        'name' => 'Gereja A',
                    ],
                    'family' => [
                        'family_number' => 'KK-001-A',
                        'name' => 'Keluarga Santoso',
                        'address' => 'Jl. Kebon Jeruk No. 12',
                    ],
                ],
            ]);

        // Cek anggota keluarga lain ikut terbawa
        $familyMembers = $response->json('data.family.members');
        $this->assertCount(2, $familyMembers);

        // Cek sakramen terbawa
        $sacraments = $response->json('data.sacraments');
        $this->assertCount(1, $sacraments);
        $this->assertSame('sidi', $sacraments[0]['type']);
        $this->assertSame('Peneguhan Sidi', $sacraments[0]['type_label']);
        $this->assertSame('SIDI/2005/012', $sacraments[0]['certificate_number']);
        $this->assertSame('Pdt. Johanes', $sacraments[0]['official']);
    }

    public function test_api_anti_idor_member_sendiri_diizinkan(): void
    {
        $response = $this->actingAs($this->userA1)->getJson("/api/portal/members/{$this->memberA1->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $this->memberA1->id,
                    'full_name' => 'Santoso Surya',
                ],
            ]);
    }

    public function test_api_anti_idor_menolak_akses_data_member_lain_satu_gereja(): void
    {
        // User A1 mencoba mengakses data Member A2 (meskipun 1 gereja & 1 keluarga) via endpoint spesifik
        $response = $this->actingAs($this->userA1)->getJson("/api/portal/members/{$this->memberA2->id}");

        $response->assertStatus(403);
    }

    public function test_api_anti_idor_menolak_akses_data_member_gereja_lain(): void
    {
        // User A1 mencoba mengakses data Member B (Gereja B)
        $response = $this->actingAs($this->userA1)->getJson("/api/portal/members/{$this->memberB->id}");

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    // ==========================================
    // 3. JADWAL IBADAH & EVENT MENDATANG
    // ==========================================

    public function test_jadwal_ibadah_hanya_menampilkan_event_gereja_sendiri(): void
    {
        $catA = EventCategory::factory()->create(['church_id' => $this->churchA->id, 'name' => 'Ibadah Raya']);
        $catB = EventCategory::factory()->create(['church_id' => $this->churchB->id, 'name' => 'Ibadah Gereja B']);

        // Event mendatang di Gereja A
        $eventAUpcoming = Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $catA->id,
            'title' => 'Ibadah Minggu Pagi A',
            'start_datetime' => now()->addDays(2)->setHour(9)->setMinute(0),
            'end_datetime' => now()->addDays(2)->setHour(11)->setMinute(0),
            'location' => 'Gedung Utama Lt. 1',
        ]);

        // Event lampau di Gereja A (tidak boleh muncul di jadwal mendatang)
        Event::factory()->create([
            'church_id' => $this->churchA->id,
            'category_id' => $catA->id,
            'title' => 'Ibadah Minggu Lalu',
            'start_datetime' => now()->subDays(5),
            'end_datetime' => now()->subDays(5)->addHours(2),
        ]);

        // Event di Gereja B (tidak boleh bocor ke jemaat Gereja A)
        Event::factory()->create([
            'church_id' => $this->churchB->id,
            'category_id' => $catB->id,
            'title' => 'Ibadah Gereja B Mendatang',
            'start_datetime' => now()->addDays(2),
            'end_datetime' => now()->addDays(2)->addHours(2),
        ]);

        $response = $this->actingAs($this->userA1)->getJson('/api/portal/events');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('Ibadah Minggu Pagi A', $data[0]['title']);
        $this->assertSame('Ibadah Raya', $data[0]['category']);
        $this->assertSame('Gedung Utama Lt. 1', $data[0]['location']);
        $this->assertSame($eventAUpcoming->id, $data[0]['id']);

        // Uji juga alias /schedules
        $responseAlias = $this->actingAs($this->userA1)->getJson('/api/portal/schedules');
        $responseAlias->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_jadwal_ibadah_mendatang_menandai_roster_tugas_jemaat(): void
    {
        $role = MinistryRole::factory()->create([
            'church_id' => $this->churchA->id,
            'name' => 'Liturgos',
        ]);

        $event = Event::factory()->create([
            'church_id' => $this->churchA->id,
            'title' => 'Ibadah Raya Minggu',
            'start_datetime' => now()->addDays(3),
            'end_datetime' => now()->addDays(3)->addHours(2),
            'location' => 'Ruang Ibadah Utama',
        ]);

        EventRoster::factory()->create([
            'church_id' => $this->churchA->id,
            'event_id' => $event->id,
            'member_id' => $this->memberA1->id,
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($this->userA1)->getJson('/api/portal/events');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertTrue($data[0]['is_my_duty']);
        $this->assertSame('Liturgos', $data[0]['rosters'][0]['role']);
        $this->assertSame('Santoso Surya', $data[0]['rosters'][0]['person_name']);
    }

    public function test_event_detail_lintas_gereja_ditolak_404(): void
    {
        $eventB = Event::factory()->create([
            'church_id' => $this->churchB->id,
            'title' => 'Event Rahasia Gereja B',
            'start_datetime' => now()->addDays(1),
            'end_datetime' => now()->addDays(1)->addHours(2),
        ]);

        // User A coba akses event milik Gereja B via API
        $this->actingAs($this->userA1)
            ->getJson("/api/portal/events/{$eventB->id}")
            ->assertStatus(404);

        // User A coba akses event milik Gereja B via Web
        $this->actingAs($this->userA1)
            ->get("/portal/events/{$eventB->id}")
            ->assertStatus(404);
    }

    // ==========================================
    // 4. WARTA JEMAAT
    // ==========================================

    public function test_warta_jemaat_hanya_menampilkan_warta_terpublikasi_gereja_sendiri(): void
    {
        // Warta A Published
        $wartaA = WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'created_by' => $this->userA1->id,
            'title' => 'Warta Jemaat Edisi 38/2026',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'period_start' => now()->subDays(2),
            'period_end' => now()->addDays(5),
            'content' => [
                'theme' => 'Melayani dengan Sepenuh Hati',
                'reflection' => 'Tuhan memanggil kita untuk setia.',
            ],
        ]);

        // Warta A Draft (tidak boleh muncul)
        WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'created_by' => $this->userA1->id,
            'title' => 'Warta Jemaat Masih Draft',
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Warta A Terjadwal di Masa Depan (tidak boleh muncul)
        WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'created_by' => $this->userA1->id,
            'title' => 'Warta Jemaat Minggu Depan',
            'status' => 'published',
            'published_at' => now()->addDays(2),
        ]);

        // Warta Gereja B (tidak boleh muncul)
        WartaPublication::factory()->create([
            'church_id' => $this->churchB->id,
            'created_by' => $this->userB->id,
            'title' => 'Warta Gereja B',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->userA1)->getJson('/api/portal/warta');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('Warta Jemaat Edisi 38/2026', $data[0]['title']);
        $this->assertSame($wartaA->id, $data[0]['id']);

        // Detail warta
        $detail = $this->actingAs($this->userA1)->getJson("/api/portal/warta/{$wartaA->id}");
        $detail->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $wartaA->id,
                    'title' => 'Warta Jemaat Edisi 38/2026',
                    'content' => [
                        'theme' => 'Melayani dengan Sepenuh Hati',
                    ],
                ],
            ]);
    }

    public function test_warta_detail_lintas_gereja_atau_draft_ditolak_404(): void
    {
        $wartaB = WartaPublication::factory()->create([
            'church_id' => $this->churchB->id,
            'created_by' => $this->userB->id,
            'title' => 'Warta Gereja B',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // User A coba akses warta Gereja B via API
        $this->actingAs($this->userA1)
            ->getJson("/api/portal/warta/{$wartaB->id}")
            ->assertStatus(404);

        // User A coba akses warta Gereja B via Web
        $this->actingAs($this->userA1)
            ->get("/portal/warta/{$wartaB->id}")
            ->assertStatus(404);

        // Akses warta draft milik gereja sendiri tetap ditolak 404
        $wartaDraft = WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'created_by' => $this->userA1->id,
            'title' => 'Draft Warta A',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->actingAs($this->userA1)
            ->getJson("/api/portal/warta/{$wartaDraft->id}")
            ->assertStatus(404);
    }

    // ==========================================
    // 5. ISOLASI ROLE & GERBANG PANEL FILAMENT
    // ==========================================

    public function test_user_role_member_tidak_bisa_mengakses_panel_filament(): void
    {
        $panel = Filament::getPanel('admin');

        // canAccessPanel harus false
        $this->assertFalse($this->userA1->canAccessPanel($panel));

        // Akses route panel admin harus ditolak (403)
        $this->actingAs($this->userA1)
            ->get('/admin')
            ->assertStatus(403);
    }

    // ==========================================
    // 6. TAMPILAN WEB (PIXEL UI)
    // ==========================================

    public function test_halaman_web_portal_render_sukses(): void
    {
        // 1. Halaman Login
        $this->get('/portal/login')->assertStatus(200);

        // 2. Halaman Dashboard (redirect ke profile)
        $this->actingAs($this->userA1)
            ->get('/portal')
            ->assertRedirect(route('portal.profile'));

        // 3. Halaman Profile
        $this->actingAs($this->userA1)
            ->get('/portal/profile')
            ->assertStatus(200)
            ->assertSee('Santoso Surya')
            ->assertSee('Keluarga Santoso')
            ->assertSee('3171011111110001');

        // 4. Halaman Jadwal Ibadah
        Event::factory()->create([
            'church_id' => $this->churchA->id,
            'title' => 'Ibadah Subuh Indah',
            'start_datetime' => now()->addDays(1),
            'end_datetime' => now()->addDays(1)->addHour(),
        ]);

        $this->actingAs($this->userA1)
            ->get('/portal/events')
            ->assertStatus(200)
            ->assertSee('Ibadah Subuh Indah');

        // 5. Halaman Warta
        $pub = WartaPublication::factory()->create([
            'church_id' => $this->churchA->id,
            'created_by' => $this->userA1->id,
            'title' => 'Warta Pekan Ini',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->actingAs($this->userA1)
            ->get('/portal/warta')
            ->assertStatus(200)
            ->assertSee('Warta Pekan Ini');

        // 6. Halaman Detail Warta
        $this->actingAs($this->userA1)
            ->get("/portal/warta/{$pub->id}")
            ->assertStatus(200)
            ->assertSee('Warta Pekan Ini');
    }
}
