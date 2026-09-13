<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Clusters\Demographics\Resources\Members\MemberResource;
use App\Models\Church;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MemberCsvTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'nik,full_name,gender,birth_date,family_number,family_relation,status';

    private function churchAdmin(Church $church): User
    {
        return User::factory()->create(['church_id' => $church->id, 'role' => 'church_admin']);
    }

    private function financeAdmin(Church $church): User
    {
        return User::factory()->create(['church_id' => $church->id, 'role' => 'finance_admin']);
    }

    public function test_export_church_admin_hanya_melihat_gereja_sendiri(): void
    {
        $churchA = Church::factory()->create(['name' => 'CSV A']);
        $churchB = Church::factory()->create(['name' => 'CSV B']);

        $memberA = Member::factory()->create(['church_id' => $churchA->id, 'full_name' => 'Anggota A', 'id_card_number' => '3171010101']);
        Member::factory()->create(['church_id' => $churchB->id, 'full_name' => 'Anggota B', 'id_card_number' => '3171020202']);

        $this->actingAs($this->churchAdmin($churchA));

        $content = $this->get('/admin/csv-jemaat/export')->assertOk()->getContent();

        $this->assertStringContainsString(self::HEADER, $content);
        $this->assertStringContainsString('Anggota A', $content);
        $this->assertStringNotContainsString('Anggota B', $content);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content); // BOM
    }

    public function test_export_finance_admin_ditolak_403(): void
    {
        $churchA = Church::factory()->create();
        $this->actingAs($this->financeAdmin($churchA));

        $this->get('/admin/csv-jemaat/export')->assertForbidden();
    }

    public function test_export_super_admin_bisa_pilih_gereja_target(): void
    {
        $churchA = Church::factory()->create(['name' => 'SA A']);
        $churchB = Church::factory()->create(['name' => 'SA B']);

        Member::factory()->create(['church_id' => $churchA->id, 'full_name' => 'Milik A', 'id_card_number' => '3171030303']);
        Member::factory()->create(['church_id' => $churchB->id, 'full_name' => 'Milik B', 'id_card_number' => '3171040404']);

        $super = User::factory()->create(['role' => 'super_admin', 'church_id' => $churchA->id]);
        $this->actingAs($super);

        $content = $this->get('/admin/csv-jemaat/export?church_id='.$churchB->id)->assertOk()->getContent();

        $this->assertStringContainsString('Milik B', $content);
        $this->assertStringNotContainsString('Milik A', $content);
    }

    public function test_import_valid_membuat_member_dan_family(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $csv = self::HEADER."\n".
            "3201010505,Budi Santoso,m,1990-01-01,KK-001,kepala_keluarga,aktif\n".
            "3201010606,Siti Aminah,f,1993-02-02,KK-001,istri,aktif\n";

        $response = $this->post('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('jemaat.csv', $csv),
        ])->assertOk()->json();

        $this->assertSame(2, $response['imported']);
        $this->assertSame(0, $response['updated']);
        $this->assertDatabaseHas('members', ['church_id' => $church->id, 'id_card_number' => '3201010505', 'full_name' => 'Budi Santoso']);
        $this->assertDatabaseHas('members', ['church_id' => $church->id, 'id_card_number' => '3201010606']);
        $this->assertSame(1, Family::where('church_id', $church->id)->where('family_number', 'KK-001')->count());
    }

    public function test_import_duplikat_dalam_file_ditolak(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $csv = self::HEADER."\n".
            "3201010505,Budi Santoso,m,1990-01-01,KK-001,kepala_keluarga,aktif\n".
            "3201010505,Budi Lagi,m,1991-01-01,KK-002,kepala_keluarga,aktif\n";

        $this->postJson('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('jemaat.csv', $csv),
        ])->assertStatus(422);

        // All-or-nothing: tidak ada baris yang tertulis.
        $this->assertDatabaseMissing('members', ['church_id' => $church->id, 'id_card_number' => '3201010505']);
    }

    public function test_import_header_tidak_sesuai_ditolak_422(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $csv = "nama,nomor\nBudi,1\n";

        $this->postJson('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('jemaat.csv', $csv),
        ])->assertStatus(422);
    }

    public function test_import_finance_admin_ditolak_403(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->financeAdmin($church));

        $this->post('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('jemaat.csv', self::HEADER."\n"),
        ])->assertForbidden();
    }

    public function test_import_super_admin_masuk_gereja_target_bukan_gereja_aktor(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();

        $super = User::factory()->create(['role' => 'super_admin', 'church_id' => $churchA->id]);
        $this->actingAs($super);

        $csv = self::HEADER."\n3201010707,Candra Kirana,f,1995-05-05,KK-B,kepala_keluarga,aktif\n";

        $this->post('/admin/csv-jemaat/import', [
            'church_id' => $churchB->id,
            'file' => UploadedFile::fake()->createWithContent('jemaat.csv', $csv),
        ])->assertOk();

        $this->assertDatabaseHas('members', ['church_id' => $churchB->id, 'id_card_number' => '3201010707']);
        $this->assertDatabaseMissing('members', ['church_id' => $churchA->id, 'id_card_number' => '3201010707']);
    }

    public function test_import_kedua_kali_memperbarui_bukan_menduplikasi(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $csv1 = self::HEADER."\n3201010808,Dodi, m,1980-03-03,KK-002,kepala_keluarga,aktif\n";
        $this->post('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('a.csv', $csv1),
        ])->assertOk();

        $csv2 = self::HEADER."\n3201010808,Dodi Update,m,1980-03-03,KK-002,kepala_keluarga,aktif\n";
        $response = $this->post('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('b.csv', $csv2),
        ])->assertOk()->json();

        $this->assertSame(1, $response['updated']);
        $this->assertSame(0, $response['imported']);
        $this->assertSame(1, Member::where('church_id', $church->id)->where('id_card_number', '3201010808')->count());
        $this->assertDatabaseHas('members', ['church_id' => $church->id, 'id_card_number' => '3201010808', 'full_name' => 'Dodi Update']);
    }

    public function test_template_bisa_diunduh_oleh_church_admin(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $response = $this->get('/admin/csv-jemaat/template');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(self::HEADER, $response->getContent());
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->getContent());
    }

    public function test_template_finance_admin_ditolak_403(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->financeAdmin($church));

        $this->get('/admin/csv-jemaat/template')->assertForbidden();
    }

    public function test_export_lalu_import_kembali_berhasil_dengan_bom(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $family = Family::factory()->create(['church_id' => $church->id, 'family_number' => 'KK-999']);
        Member::factory()->create([
            'church_id' => $church->id,
            'family_id' => $family->id,
            'full_name' => 'Jemaat Ekspor',
            'id_card_number' => '3171099999',
            'gender' => 'm',
            'birth_date' => '1992-05-10',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
        ]);

        $exportedCsv = $this->get('/admin/csv-jemaat/export')->assertOk()->getContent();

        $response = $this->post('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('exported.csv', $exportedCsv),
        ])->assertOk()->json();

        $this->assertSame(1, $response['updated']);
        $this->assertSame(0, $response['imported']);
    }

    public function test_import_memulihkan_member_soft_deleted(): void
    {
        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $family = Family::factory()->create(['church_id' => $church->id, 'family_number' => 'KK-RESTORE']);
        $member = Member::factory()->create([
            'church_id' => $church->id,
            'family_id' => $family->id,
            'full_name' => 'Jemaat Dihapus',
            'id_card_number' => '3171088888',
        ]);
        $member->delete();
        $this->assertSoftDeleted($member);

        $csv = self::HEADER."\n3171088888,Jemaat Pulih,m,1985-06-06,KK-RESTORE,kepala_keluarga,aktif\n";

        $response = $this->post('/admin/csv-jemaat/import', [
            'file' => UploadedFile::fake()->createWithContent('restore.csv', $csv),
        ])->assertOk()->json();

        $this->assertSame(1, $response['updated']);
        $member->refresh();
        $this->assertFalse($member->trashed());
        $this->assertSame('Jemaat Pulih', $member->full_name);
    }

    public function test_halaman_list_members_render_dengan_tombol_csv(): void
    {
        $this->withoutVite();

        $church = Church::factory()->create();
        $this->actingAs($this->churchAdmin($church));

        $this->get(MemberResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Template CSV')
            ->assertSee('Export CSV');
    }
}
