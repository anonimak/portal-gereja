<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Imports\EventScheduleImport;
use App\Imports\FinanceMasterImport;
use App\Imports\MemberFamilyImport;
use App\Imports\OfficialMinistryImport;
use App\Models\AuditLog;
use App\Models\Church;
use App\Models\EventCategory;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\MinistryRole;
use App\Models\Official;
use App\Models\RecurringSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DataMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_download_routes_accessible_by_church_admin(): void
    {
        $church = Church::factory()->create();
        $admin = User::factory()->create(['church_id' => $church->id, 'role' => 'church_admin']);

        $this->actingAs($admin);

        foreach (['jemaat', 'keuangan', 'pelayan', 'acara'] as $mod) {
            $response = $this->get(route('data-migration.template', ['module' => $mod]));
            $response->assertOk();
            $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type') ?? '');
        }
    }

    public function test_template_download_forbidden_for_guests_and_unauthorized(): void
    {
        $response = $this->get('/admin/migrasi-data/template/jemaat');
        $response->assertRedirect('/login');

        $church = Church::factory()->create();
        $jemaat = User::factory()->create(['church_id' => $church->id, 'role' => 'report_viewer']);
        $this->actingAs($jemaat);
        $this->get('/admin/migrasi-data/template/jemaat')->assertForbidden();
    }

    public function test_member_family_import_groups_family_and_upserts_member(): void
    {
        $church = Church::factory()->create();
        $importer = new MemberFamilyImport($church->id);

        $rows = collect([
            collect([
                'no_kk' => 'KK-999',
                'nama_keluarga' => 'Keluarga Budi',
                'alamat' => 'Jl. Mawar No. 1',
                'nik' => '1801010101850001',
                'nama_lengkap' => 'Budi Santoso',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Lampung',
                'tanggal_lahir' => '1985-05-17',
                'hubungan_keluarga' => 'kepala_keluarga',
                'nomor_telepon' => '081272001234',
                'status_anggota' => 'aktif',
                'status_baptis' => 'sudah',
                'tanggal_baptis' => '1985-08-20',
                'nomor_surat_baptis' => 'BAP-001',
                'status_sidi' => 'sudah',
                'tanggal_sidi' => '2002-04-14',
                'nomor_surat_sidi' => 'SDI-001',
                'status_nikah' => 'sudah',
                'tanggal_nikah' => '2010-09-12',
                'nomor_surat_nikah' => 'NKH-001',
            ]),
            collect([
                'no_kk' => 'KK-999',
                'nama_keluarga' => 'Keluarga Budi',
                'alamat' => 'Jl. Mawar No. 1',
                'nik' => '1801014101870002',
                'nama_lengkap' => 'Siti Aminah',
                'jenis_kelamin' => 'Perempuan',
                'tempat_lahir' => 'Pesawaran',
                'tanggal_lahir' => '1987-11-23',
                'hubungan_keluarga' => 'istri',
                'nomor_telepon' => '081272005678',
                'status_anggota' => 'aktif',
                'status_baptis' => 'sudah',
                'tanggal_baptis' => '1987-12-25',
                'nomor_surat_baptis' => 'BAP-002',
                'status_sidi' => 'belum',
                'status_nikah' => 'sudah',
                'tanggal_nikah' => '2010-09-12',
            ]),
        ]);

        $importer->collection($rows);
        $summary = $importer->getSummary();

        $this->assertEquals(2, $summary->totalCreated);
        $this->assertEquals(0, $summary->totalSkipped);

        // Pastikan hanya 1 Family terbentuk
        $families = Family::where('church_id', $church->id)->get();
        $this->assertCount(1, $families);
        $this->assertEquals('KK-999', $families->first()->family_number);

        // Pastikan kedua member tertaut ke family tsb
        $members = Member::where('church_id', $church->id)->get();
        $this->assertCount(2, $members);

        $budi = $members->firstWhere('id_card_number', '1801010101850001');
        $this->assertNotNull($budi);
        $this->assertEquals($families->first()->id, $budi->family_id);
        $this->assertEquals('m', $budi->gender);

        // Pastikan sakramen otomatis dibuat untuk Budi (baptis, sidi, nikah)
        $sacraments = MemberSacrament::where('member_id', $budi->id)->get();
        $this->assertCount(3, $sacraments);
        $this->assertTrue($sacraments->contains('type', 'baptis_dewasa'));
        $this->assertTrue($sacraments->contains('type', 'sidi'));
        $this->assertTrue($sacraments->contains('type', 'nikah'));

        // Test Upsert: Jalankan baris yang sama dengan perubahan nomor telepon & alamat
        $updateRows = collect([
            collect([
                'no_kk' => 'KK-999',
                'nama_keluarga' => 'Keluarga Budi Santoso Baru',
                'alamat' => 'Jl. Mawar No. 99 Candimas',
                'nik' => '1801010101850001',
                'nama_lengkap' => 'Budi Santoso',
                'jenis_kelamin' => 'L',
                'hubungan_keluarga' => 'kepala_keluarga',
                'nomor_telepon' => '081299998888',
                'status_anggota' => 'aktif',
            ]),
        ]);

        $importer2 = new MemberFamilyImport($church->id);
        $importer2->collection($updateRows);
        $summary2 = $importer2->getSummary();

        $this->assertEquals(0, $summary2->totalCreated);
        $this->assertEquals(1, $summary2->totalUpdated);

        $budiReloaded = Member::where('church_id', $church->id)->where('id_card_number', '1801010101850001')->first();
        $this->assertEquals('081299998888', $budiReloaded->custom_fields['phone']);
    }

    public function test_member_family_prevents_cross_church_nik_duplicate(): void
    {
        $churchA = Church::factory()->create(['name' => 'Gereja A']);
        $churchB = Church::factory()->create(['name' => 'Gereja B']);

        // Budi ada di Gereja A
        Member::factory()->create([
            'church_id' => $churchA->id,
            'id_card_number' => '1801010101850001',
            'full_name' => 'Budi Gereja A',
        ]);

        // Coba impor NIK yang sama ke Gereja B
        $importerB = new MemberFamilyImport($churchB->id);
        $rows = collect([
            collect([
                'no_kk' => 'KK-B',
                'nama_lengkap' => 'Budi Duplikat',
                'nik' => '1801010101850001',
                'jenis_kelamin' => 'L',
                'alamat' => 'Alamat B',
            ]),
        ]);

        $importerB->collection($rows);
        $summary = $importerB->getSummary();

        $this->assertEquals(0, $summary->totalCreated);
        $this->assertEquals(1, $summary->totalSkipped);
        $this->assertNotEmpty($summary->errors);
        $this->assertStringContainsString('sudah terdaftar pada jemaat gereja lain', $summary->errors[0]['message']);
    }

    public function test_finance_master_import_normalizes_types(): void
    {
        $church = Church::factory()->create();
        $importer = new FinanceMasterImport($church->id);
        $sheets = $importer->sheets();

        // Sheet 0: Pos Kas & Dana
        $fundRows = collect([
            collect(['nama_pos_dana' => 'Kas Pembangunan Gereja']),
            collect(['nama_pos_dana' => 'Dana Diakonia Khusus']),
        ]);
        $sheets[0]->collection($fundRows);

        // Sheet 1: Kategori Transaksi
        $categoryRows = collect([
            collect(['nama_kategori' => 'Kolekte Ibadah', 'tipe' => 'Pemasukan']),
            collect(['nama_kategori' => 'Persepuluhan Warga', 'tipe' => 'Masuk']),
            collect(['nama_kategori' => 'Listrik & Air', 'tipe' => 'Pengeluaran']),
            collect(['nama_kategori' => 'Santunan Lansia', 'tipe' => 'credit']),
        ]);
        $sheets[1]->collection($categoryRows);

        $summary = $importer->getSummary();
        $this->assertEquals(6, $summary->totalCreated);
        $this->assertEquals(0, $summary->totalSkipped);

        // Cek Fund
        $this->assertTrue(Fund::where('church_id', $church->id)->where('name', 'Kas Pembangunan Gereja')->exists());

        // Cek Normalisasi Kategori
        $kolekte = FinancialCategory::where('church_id', $church->id)->where('name', 'Kolekte Ibadah')->first();
        $this->assertEquals('debit', $kolekte->type);

        $persepuluhan = FinancialCategory::where('church_id', $church->id)->where('name', 'Persepuluhan Warga')->first();
        $this->assertEquals('debit', $persepuluhan->type);

        $listrik = FinancialCategory::where('church_id', $church->id)->where('name', 'Listrik & Air')->first();
        $this->assertEquals('credit', $listrik->type);
    }

    public function test_official_ministry_import_resolves_member_and_guest(): void
    {
        $church = Church::factory()->create();

        // Warga jemaat yang akan dijadikan majelis
        $member = Member::factory()->create([
            'church_id' => $church->id,
            'id_card_number' => '1801019999990001',
            'full_name' => 'Yohanes Penatua',
        ]);

        $importer = new OfficialMinistryImport($church->id);
        $sheets = $importer->sheets();

        // Sheet 0: Jabatan
        $sheets[0]->collection(collect([
            collect(['nama_jabatan' => 'Penatua Jemaat']),
            collect(['nama_jabatan' => 'Diaken']),
        ]));

        // Sheet 1: Pejabat
        $sheets[1]->collection(collect([
            collect([
                'tipe_pejabat' => 'majelis_lokal',
                'nik_anggota' => '1801019999990001',
                'nama_pejabat' => 'Yohanes Penatua',
                'tanggal_mulai_jabatan' => '2024-01-01',
                'tanggal_selesai_jabatan' => null,
            ]),
            collect([
                'tipe_pejabat' => 'pelayan_tamu',
                'nik_anggota' => null,
                'nama_pejabat' => 'Pdt. Tamu Sinode',
                'gereja_asal' => 'GKSBS Seputih Banyak',
                'tanggal_mulai_jabatan' => '2025-06-01',
                'tanggal_selesai_jabatan' => '2025-06-30',
            ]),
        ]));

        $summary = $importer->getSummary();
        $this->assertEquals(4, $summary->totalCreated);

        $officialLokal = Official::where('church_id', $church->id)->where('member_id', $member->id)->first();
        $this->assertNotNull($officialLokal);
        $this->assertEquals('majelis_lokal', $officialLokal->type);

        $officialTamu = Official::where('church_id', $church->id)->where('external_name', 'Pdt. Tamu Sinode')->first();
        $this->assertNotNull($officialTamu);
        $this->assertEquals('pelayan_tamu', $officialTamu->type);
        $this->assertEquals('GKSBS Seputih Banyak', $officialTamu->origin_church);
    }

    public function test_event_schedule_import_creates_category_and_schedule(): void
    {
        $church = Church::factory()->create();
        $importer = new EventScheduleImport($church->id);
        $sheets = $importer->sheets();

        // Sheet 0: Kategori
        $sheets[0]->collection(collect([
            collect(['nama_kategori' => 'Ibadah Raya Minggu']),
        ]));

        // Sheet 1: Jadwal Berulang
        $sheets[1]->collection(collect([
            collect([
                'kategori' => 'Ibadah Raya Minggu',
                'judul_jadwal' => 'Ibadah Pagi Candimas',
                'lokasi' => 'Gedung Gereja Utama',
                'frekuensi' => 'weekly',
                'hari_pelaksanaan' => 'Minggu',
                'jam_mulai' => '08:30',
                'jam_selesai' => '10:30',
                'tanggal_mulai_berlaku' => '2026-01-01',
                'keterangan' => 'Ibadah umum',
            ]),
        ]));

        $summary = $importer->getSummary();
        $this->assertEquals(2, $summary->totalCreated);

        $schedule = RecurringSchedule::where('church_id', $church->id)->where('title', 'Ibadah Pagi Candimas')->first();
        $this->assertNotNull($schedule);
        $this->assertEquals([0], $schedule->days_of_week);
        $this->assertEquals('weekly', $schedule->frequency);
        $this->assertEquals('08:30:00', $schedule->start_time);
    }
}
