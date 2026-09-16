<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BirthRecord;
use App\Models\Church;
use App\Models\DeathRecord;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCategory;
use App\Models\EventRoster;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\GuidanceProgram;
use App\Models\GuidanceSession;
use App\Models\GuidanceSessionMember;
use App\Models\GuidanceTemplate;
use App\Models\Marriage;
use App\Models\MeetingMinutes;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\MinistryRole;
use App\Models\Official;
use App\Models\RecurringSchedule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WartaPublication;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan gereja-gereja demo sudah ada
        $churchesData = [
            [
                'code' => 'GKSBS-KEL-CDM',
                'name' => 'Jemaat Kelompok Candimas',
                'synod' => 'Gereja Kristen Sumatera Bagian Selatan (GKSBS) — Klasis Tulang Bawang',
                'address' => 'Jl. Way Abung No. 45, Kel. Candimas, Kec. Natar, Lampung Selatan',
                'phone' => '081272001234',
                'email' => 'sekretariat.candimas@gksbs-filadelfia.org',
                'website' => 'https://candimas.gksbs-filadelfia.org',
            ],
            [
                'code' => 'GKSBS-KEL-TRM',
                'name' => 'Jemaat Kelompok Trimulyo',
                'synod' => 'Gereja Kristen Sumatera Bagian Selatan (GKSBS) — Klasis Tulang Bawang',
                'address' => 'Jl. Raya Trimulyo No. 12, Trimulyo, Kec. Tegineneng, Pesawaran',
                'phone' => '081272005678',
                'email' => 'sekretariat.trimulyo@gksbs-filadelfia.org',
                'website' => 'https://trimulyo.gksbs-filadelfia.org',
            ],
            [
                'code' => 'GKSBS-KEL-MRG',
                'name' => 'Jemaat Kelompok Margomulyo',
                'synod' => 'Gereja Kristen Sumatera Bagian Selatan (GKSBS) — Klasis Tulang Bawang',
                'address' => 'Jl. Margomulyo Indah No. 88, Margomulyo, Lampung Selatan',
                'phone' => '081272009012',
                'email' => 'sekretariat.margomulyo@gksbs-filadelfia.org',
                'website' => 'https://margomulyo.gksbs-filadelfia.org',
            ],
        ];

        $churches = [];
        foreach ($churchesData as $c) {
            $church = Church::updateOrCreate(
                ['code' => $c['code']],
                [
                    'name' => $c['name'],
                    'synod' => $c['synod'],
                    'address' => $c['address'],
                    'phone' => $c['phone'],
                    'email' => $c['email'],
                    'website' => $c['website'],
                ]
            );
            $churches[$c['code']] = $church;

            // Pastikan dana & kategori keuangan default ada
            if (Fund::where('church_id', $church->id)->count() === 0) {
                (new DefaultFinanceSeeder)->run($church->id);
            }

            // Pastikan template bimbingan (Pra-Sidi & Pra-Nikah) ada
            if (GuidanceTemplate::where('church_id', $church->id)->count() === 0) {
                (new GuidanceTemplateSeeder)->run($church->id);
            }
        }

        $candimas = $churches['GKSBS-KEL-CDM'];
        $trimulyo = $churches['GKSBS-KEL-TRM'];
        $margomulyo = $churches['GKSBS-KEL-MRG'];

        // 2. Pastikan Akun Administrator Ada
        $adminCandimas = User::firstOrCreate(
            ['email' => 'admin.candimas@gksbs-filadelfia.org'],
            [
                'name' => 'Admin Kelompok Candimas',
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'role' => 'church_admin',
            ]
        );

        $adminTrimulyo = User::firstOrCreate(
            ['email' => 'admin.trimulyo@gksbs-filadelfia.org'],
            [
                'name' => 'Admin Kelompok Trimulyo',
                'password' => Hash::make('password'),
                'church_id' => $trimulyo->id,
                'role' => 'church_admin',
            ]
        );

        $adminMargomulyo = User::firstOrCreate(
            ['email' => 'admin.margomulyo@gksbs-filadelfia.org'],
            [
                'name' => 'Admin Kelompok Margomulyo',
                'password' => Hash::make('password'),
                'church_id' => $margomulyo->id,
                'role' => 'church_admin',
            ]
        );

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@gereja.test'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'role' => 'super_admin',
            ]
        );

        // Staff roles untuk Candimas
        User::updateOrCreate(
            ['email' => 'finance.candimas@gksbs-filadelfia.org'],
            [
                'name' => 'Bendahara Candimas',
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'role' => 'finance_admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'warta.candimas@gksbs-filadelfia.org'],
            [
                'name' => 'Editor Warta Candimas',
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'role' => 'warta_editor',
            ]
        );

        User::updateOrCreate(
            ['email' => 'jemaat.admin@gksbs-filadelfia.org'],
            [
                'name' => 'Admin Jemaat Candimas',
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'role' => 'jemaat_admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'viewer.candimas@gksbs-filadelfia.org'],
            [
                'name' => 'Viewer Laporan Candimas',
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'role' => 'report_viewer',
            ]
        );

        // 3. Reset data operasional Candimas secara bersih (idempotent seeder)
        $isSqlite = DB::getDriverName() === 'sqlite';
        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        // Putus relasi member_id pada users terlebih dahulu
        DB::table('users')->where('church_id', $candimas->id)->update(['member_id' => null]);
        DB::table('users')->where('role', 'member')->where('church_id', $candimas->id)->delete();

        DB::table('warta_publications')->whereIn('church_id', [$candimas->id, $trimulyo->id, $margomulyo->id])->delete();
        DB::table('meeting_minutes')->where('church_id', $candimas->id)->delete();
        DB::table('event_attendances')->where('church_id', $candimas->id)->delete();
        DB::table('event_rosters')->where('church_id', $candimas->id)->delete();
        DB::table('events')->where('church_id', $candimas->id)->delete();
        DB::table('recurring_schedules')->where('church_id', $candimas->id)->delete();
        DB::table('transactions')->where('church_id', $candimas->id)->delete();
        DB::table('guidance_session_members')->where('church_id', $candimas->id)->delete();
        DB::table('guidance_sessions')->where('church_id', $candimas->id)->delete();
        DB::table('guidance_programs')->where('church_id', $candimas->id)->delete();
        DB::table('member_sacraments')->where('church_id', $candimas->id)->delete();
        DB::table('marriages')->where('church_id', $candimas->id)->delete();
        DB::table('member_deaths')->where('church_id', $candimas->id)->delete();
        DB::table('birth_records')->where('church_id', $candimas->id)->delete();
        DB::table('officials')->where('church_id', $candimas->id)->delete();
        DB::table('members')->where('church_id', $candimas->id)->delete();
        DB::table('families')->where('church_id', $candimas->id)->delete();
        DB::table('ministry_roles')->where('church_id', $candimas->id)->delete();
        DB::table('event_categories')->where('church_id', $candimas->id)->delete();

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        // 4. Peran Pelayanan (Ministry Roles)
        $ministryRoleNames = [
            'Pendeta',
            'Penatua / Majelis',
            'Diaken',
            'Pemimpin Pujian (Worship Leader)',
            'Pemusik / Organis',
            'Singer',
            'Penerima Tamu (Usher)',
            'Operator Multimedia & Sound',
            'Guru Sekolah Minggu',
        ];

        $roles = [];
        foreach ($ministryRoleNames as $name) {
            $roles[$name] = MinistryRole::create([
                'church_id' => $candimas->id,
                'name' => $name,
            ]);
        }

        // 5. Kategori Acara (Event Categories)
        $eventCategoryNames = [
            'Ibadah Raya Minggu',
            'Ibadah Pemuda & Remaja',
            'Persekutuan Doa & PA',
            'Ibadah Sekolah Minggu',
            'Ibadah Rumah Tangga',
        ];

        $categories = [];
        foreach ($eventCategoryNames as $name) {
            $categories[$name] = EventCategory::create([
                'church_id' => $candimas->id,
                'name' => $name,
            ]);
        }

        // 6. Data Keluarga & Jemaat (Families & Members)
        // Keluarga 1: Surya (Demo Utama Portal Jemaat)
        $famSurya = Family::create([
            'church_id' => $candimas->id,
            'family_number' => 'KK-CDM-001',
            'name' => 'Keluarga Santoso Surya',
            'address' => 'Jl. Melati No. 12, RT 02/RW 01, Candimas, Kec. Natar',
        ]);

        $mSantoso = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSurya->id,
            'id_card_number' => '1801041405800001',
            'full_name' => 'Santoso Surya',
            'gender' => 'm',
            'birth_place' => 'Tanjung Karang',
            'birth_date' => '1980-05-14',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081272001122', 'pekerjaan' => 'PNS / Tenaga Pendidik', 'golongan_darah' => 'O'],
        ]);

        $mMaria = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSurya->id,
            'id_card_number' => '1801042208830002',
            'full_name' => 'Maria Magdalena Surya',
            'gender' => 'f',
            'birth_place' => 'Bandar Lampung',
            'birth_date' => '1983-08-22',
            'family_relation' => 'istri',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081272001123', 'pekerjaan' => 'Wiraswasta', 'golongan_darah' => 'A'],
        ]);

        $mDaniel = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSurya->id,
            'id_card_number' => '1801041503080003',
            'full_name' => 'Daniel Surya',
            'gender' => 'm',
            'birth_place' => 'Candimas',
            'birth_date' => '2008-03-15',
            'family_relation' => 'anak',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081369001124', 'pendidikan' => 'SMA'],
        ]);

        $mDebora = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSurya->id,
            'id_card_number' => '1801041011140004',
            'full_name' => 'Debora Surya',
            'gender' => 'f',
            'birth_place' => 'Candimas',
            'birth_date' => '2014-11-10',
            'family_relation' => 'anak',
            'status' => 'aktif',
            'custom_fields' => ['pendidikan' => 'SD'],
        ]);

        // Keluarga 2: Wijaya (Majelis & Organis)
        $famWijaya = Family::create([
            'church_id' => $candimas->id,
            'family_number' => 'KK-CDM-002',
            'name' => 'Keluarga Hendra Wijaya',
            'address' => 'Jl. Mawar Indah Blok B3 No. 7, Candimas, Kec. Natar',
        ]);

        $mHendra = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famWijaya->id,
            'id_card_number' => '1801041802780001',
            'full_name' => 'Hendra Wijaya',
            'gender' => 'm',
            'birth_place' => 'Metro',
            'birth_date' => '1978-02-18',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081273002233', 'pekerjaan' => 'Wiraswasta', 'jabatan_gereja' => 'Penatua'],
        ]);

        $mYohana = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famWijaya->id,
            'id_card_number' => '1801042506810002',
            'full_name' => 'Yohana Wijaya',
            'gender' => 'f',
            'birth_place' => 'Kotabumi',
            'birth_date' => '1981-06-25',
            'family_relation' => 'istri',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081273002234', 'pekerjaan' => 'Apoteker', 'jabatan_gereja' => 'Diaken'],
        ]);

        $mSamuel = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famWijaya->id,
            'id_card_number' => '1801041209040003',
            'full_name' => 'Samuel Wijaya',
            'gender' => 'm',
            'birth_place' => 'Bandar Lampung',
            'birth_date' => '2004-09-12',
            'family_relation' => 'anak',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081370002235', 'pekerjaan' => 'Mahasiswa / Pemusik'],
        ]);

        $mRachel = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famWijaya->id,
            'id_card_number' => '1801040512090004',
            'full_name' => 'Rachel Wijaya',
            'gender' => 'f',
            'birth_place' => 'Candimas',
            'birth_date' => '2009-12-05',
            'family_relation' => 'anak',
            'status' => 'aktif',
            'custom_fields' => ['pendidikan' => 'SMA Kelas 1'],
        ]);

        // Keluarga 3: Sihombing (Kelahiran & Baptis Balita)
        $famSihombing = Family::create([
            'church_id' => $candimas->id,
            'family_number' => 'KK-CDM-003',
            'name' => 'Keluarga Markus Sihombing',
            'address' => 'Jl. Dahlia No. 45, RT 01/RW 03, Candimas, Kec. Natar',
        ]);

        $mMarkus = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSihombing->id,
            'id_card_number' => '1801042007850001',
            'full_name' => 'Markus Sihombing',
            'gender' => 'm',
            'birth_place' => 'Tarutung',
            'birth_date' => '1985-07-20',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081274003344', 'pekerjaan' => 'Karyawan Swasta'],
        ]);

        $mRuth = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSihombing->id,
            'id_card_number' => '1801041410870002',
            'full_name' => 'Ruth Sihombing',
            'gender' => 'f',
            'birth_place' => 'Pringsewu',
            'birth_date' => '1987-10-14',
            'family_relation' => 'istri',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081274003345', 'pekerjaan' => 'Bidan'],
        ]);

        $mTimothy = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSihombing->id,
            'id_card_number' => '1801041001250003',
            'full_name' => 'Timothy Sihombing',
            'gender' => 'm',
            'birth_place' => 'Bandar Lampung',
            'birth_date' => '2025-01-10',
            'family_relation' => 'anak',
            'status' => 'aktif',
        ]);

        // Keluarga 4: Pranoto (Catatan Kematian / Lansia)
        $famPranoto = Family::create([
            'church_id' => $candimas->id,
            'family_number' => 'KK-CDM-004',
            'name' => 'Keluarga Yohanes Pranoto',
            'address' => 'Jl. Kenanga No. 8, Candimas, Kec. Natar',
        ]);

        $mYohanes = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famPranoto->id,
            'id_card_number' => '1801041204520001',
            'full_name' => 'Yohanes Pranoto',
            'gender' => 'm',
            'birth_place' => 'Yogyakarta',
            'birth_date' => '1952-04-12',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081275004455', 'pekerjaan' => 'Pensiunan'],
        ]);

        $mElisabeth = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famPranoto->id,
            'id_card_number' => '1801040809550002',
            'full_name' => 'Elisabeth Pranoto',
            'gender' => 'f',
            'birth_place' => 'Solo',
            'birth_date' => '1955-09-08',
            'family_relation' => 'istri',
            'status' => 'meninggal',
        ]);

        $mAndreasP = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famPranoto->id,
            'id_card_number' => '1801042011860003',
            'full_name' => 'Andreas Pranoto',
            'gender' => 'm',
            'birth_place' => 'Candimas',
            'birth_date' => '1986-11-20',
            'family_relation' => 'anak',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081275004456', 'pekerjaan' => 'Dosen'],
        ]);

        // Keluarga 5: Pasangan Muda Menikah
        $famChristian = Family::create([
            'church_id' => $candimas->id,
            'family_number' => 'KK-CDM-005',
            'name' => 'Keluarga David Christian',
            'address' => 'Griya Asri Candimas No. 19, Natar, Lampung Selatan',
        ]);

        $mDavid = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famChristian->id,
            'id_card_number' => '1801042803960001',
            'full_name' => 'David Christian',
            'gender' => 'm',
            'birth_place' => 'Bandar Lampung',
            'birth_date' => '1996-03-28',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081276005566', 'pekerjaan' => 'Software Engineer'],
        ]);

        $mEster = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famChristian->id,
            'id_card_number' => '1801041708980002',
            'full_name' => 'Ester Natalia',
            'gender' => 'f',
            'birth_place' => 'Palembang',
            'birth_date' => '1998-08-17',
            'family_relation' => 'istri',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081276005567', 'pekerjaan' => 'Desainer Grafis'],
        ]);

        // Keluarga 6: Setyawan
        $famSetyawan = Family::create([
            'church_id' => $candimas->id,
            'family_number' => 'KK-CDM-006',
            'name' => 'Keluarga Budi Setyawan',
            'address' => 'Jl. Cempaka Putih No. 15, Candimas, Kec. Natar',
        ]);

        $mBudi = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSetyawan->id,
            'id_card_number' => '1801041006750001',
            'full_name' => 'Budi Setyawan',
            'gender' => 'm',
            'birth_place' => 'Semarang',
            'birth_date' => '1975-06-10',
            'family_relation' => 'kepala_keluarga',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081277006677', 'pekerjaan' => 'PNS'],
        ]);

        $mSusana = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSetyawan->id,
            'id_card_number' => '1801041510790002',
            'full_name' => 'Susana Setyawan',
            'gender' => 'f',
            'birth_place' => 'Bandar Lampung',
            'birth_date' => '1979-10-15',
            'family_relation' => 'istri',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081277006678', 'pekerjaan' => 'Guru'],
        ]);

        $mJonathan = Member::create([
            'church_id' => $candimas->id,
            'family_id' => $famSetyawan->id,
            'id_card_number' => '1801042512060003',
            'full_name' => 'Jonathan Setyawan',
            'gender' => 'm',
            'birth_place' => 'Candimas',
            'birth_date' => '2006-12-25',
            'family_relation' => 'anak',
            'status' => 'aktif',
            'custom_fields' => ['phone' => '081371006679', 'pekerjaan' => 'Operator Multimedia Gereja'],
        ]);

        // 7. Member Portal User (Pengujian Web & API Portal Mandiri)
        User::updateOrCreate(
            ['email' => 'jemaat@gereja.test'],
            [
                'name' => $mSantoso->full_name,
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'member_id' => $mSantoso->id,
                'role' => 'member',
                'api_token' => 'portal-token-candimas-santoso-2026',
            ]
        );

        User::updateOrCreate(
            ['email' => 'samuel@gereja.test'],
            [
                'name' => $mSamuel->full_name,
                'password' => Hash::make('password'),
                'church_id' => $candimas->id,
                'member_id' => $mSamuel->id,
                'role' => 'member',
                'api_token' => 'portal-token-candimas-samuel-2026',
            ]
        );

        // 8. Pejabat Gereja & Pelayan (Officials)
        $pdtAndreas = Official::create([
            'church_id' => $candimas->id,
            'type' => 'pendeta_internal',
            'external_name' => 'Pdt. Andreas Nugroho, M.Th.',
            'start_date' => '2020-01-01',
            'end_date' => null,
        ]);

        $officialHendra = Official::create([
            'church_id' => $candimas->id,
            'type' => 'majelis_lokal',
            'member_id' => $mHendra->id,
            'start_date' => '2024-01-01',
            'end_date' => '2028-12-31',
        ]);

        $officialYohana = Official::create([
            'church_id' => $candimas->id,
            'type' => 'majelis_lokal',
            'member_id' => $mYohana->id,
            'start_date' => '2024-01-01',
            'end_date' => '2028-12-31',
        ]);

        $pdtStephen = Official::create([
            'church_id' => $candimas->id,
            'type' => 'pelayan_tamu',
            'external_name' => 'Pdt. Dr. Stephen Wong',
            'origin_church' => 'GKSBS Seputih Raman',
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        // 9. Template Program Bimbingan (Guidance Templates & Programs)
        $templatePraSidi = GuidanceTemplate::withoutGlobalScopes()
            ->where('church_id', $candimas->id)
            ->where('type', 'pra_sidi')
            ->first();

        $templatePraNikah = GuidanceTemplate::withoutGlobalScopes()
            ->where('church_id', $candimas->id)
            ->where('type', 'pra_nikah')
            ->first();

        $progSidi = GuidanceProgram::create([
            'church_id' => $candimas->id,
            'type' => 'pra_sidi',
            'title' => 'Katekisasi Sidi Angkatan 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-10-31',
            'status' => 'berjalan',
            'template_id' => $templatePraSidi?->id,
            'notes' => 'Bimbingan katekumenat pemuda angkatan 2026 menjelang Pengakuan Iman Percaya.',
        ]);
        if ($templatePraSidi) {
            $progSidi->instantiateFromTemplate();
            $sidiSessions = $progSidi->sessions()->get();
            foreach ($sidiSessions as $idx => $session) {
                $sessTime = Carbon::parse('2026-02-07 16:00:00')->addWeeks($idx * 2);
                $session->update([
                    'session_at' => $sessTime,
                    'location' => 'Ruang Katekisasi Candimas',
                    'official_id' => $pdtAndreas->id,
                ]);
                if ($sessTime->isPast()) {
                    GuidanceSessionMember::create([
                        'church_id' => $candimas->id,
                        'session_id' => $session->id,
                        'member_id' => $mDaniel->id,
                        'attended' => true,
                        'notes' => 'Hadir tepat waktu',
                    ]);
                    GuidanceSessionMember::create([
                        'church_id' => $candimas->id,
                        'session_id' => $session->id,
                        'member_id' => $mJonathan->id,
                        'attended' => true,
                        'notes' => 'Hadir',
                    ]);
                }
            }
        }

        $progNikah = GuidanceProgram::create([
            'church_id' => $candimas->id,
            'type' => 'pra_nikah',
            'title' => 'Bimbingan Pra-Nikah David & Ester',
            'start_date' => '2024-08-01',
            'end_date' => '2024-10-30',
            'status' => 'selesai',
            'template_id' => $templatePraNikah?->id,
            'notes' => 'Persiapan pastoral pernikahan kudus David Christian dan Ester Natalia.',
        ]);
        if ($templatePraNikah) {
            $progNikah->instantiateFromTemplate();
            $nikahSessions = $progNikah->sessions()->get();
            foreach ($nikahSessions as $idx => $session) {
                $sessTime = Carbon::parse('2024-08-03 19:00:00')->addWeeks($idx);
                $session->update([
                    'session_at' => $sessTime,
                    'location' => 'Pastori Gereja Candimas',
                    'official_id' => $pdtAndreas->id,
                ]);
                GuidanceSessionMember::create([
                    'church_id' => $candimas->id,
                    'session_id' => $session->id,
                    'member_id' => $mDavid->id,
                    'attended' => true,
                    'notes' => 'Hadir',
                ]);
                GuidanceSessionMember::create([
                    'church_id' => $candimas->id,
                    'session_id' => $session->id,
                    'member_id' => $mEster->id,
                    'attended' => true,
                    'notes' => 'Hadir',
                ]);
            }
        }

        // 10. Sakramen & Catatan Siklus Hidup (Sacraments, Birth, Marriage, Death)
        // Akta Lahir
        BirthRecord::create([
            'church_id' => $candimas->id,
            'member_id' => $mTimothy->id,
            'birth_order' => 1,
            'birth_place_full' => 'Bandar Lampung',
            'birth_date' => '2025-01-10',
            'father_name' => 'Markus Sihombing',
            'mother_name' => 'Ruth Sihombing',
            'certificate_number' => 'SKL-2025-0012',
            'issued_at' => '2025-01-20',
            'notes' => 'Anak pertama lahir dengan selamat dan sehat walafiat.',
        ]);

        // Baptis Anak
        MemberSacrament::create([
            'church_id' => $candimas->id,
            'member_id' => $mDebora->id,
            'type' => 'baptis_anak',
            'sacrament_date' => '2015-05-24',
            'official_id' => $pdtAndreas->id,
            'certificate_number' => 'BA-2015-0042',
            'issued_at' => '2015-05-24',
        ]);

        MemberSacrament::create([
            'church_id' => $candimas->id,
            'member_id' => $mTimothy->id,
            'type' => 'baptis_anak',
            'sacrament_date' => '2025-06-15',
            'official_id' => $pdtAndreas->id,
            'certificate_number' => 'BA-2025-0018',
            'issued_at' => '2025-06-15',
        ]);

        // Sidi & Baptis Dewasa
        MemberSacrament::create([
            'church_id' => $candimas->id,
            'member_id' => $mSantoso->id,
            'type' => 'sidi',
            'sacrament_date' => '1998-04-12',
            'official_id' => $pdtAndreas->id,
            'certificate_number' => 'SD-1998-0015',
            'issued_at' => '1998-04-12',
        ]);

        MemberSacrament::create([
            'church_id' => $candimas->id,
            'member_id' => $mMaria->id,
            'type' => 'baptis_dewasa',
            'sacrament_date' => '2001-12-25',
            'official_id' => $pdtAndreas->id,
            'certificate_number' => 'BD-2001-0028',
            'issued_at' => '2001-12-25',
        ]);

        MemberSacrament::create([
            'church_id' => $candimas->id,
            'member_id' => $mSamuel->id,
            'type' => 'sidi',
            'sacrament_date' => '2022-04-17',
            'official_id' => $pdtAndreas->id,
            'certificate_number' => 'SD-2022-0009',
            'issued_at' => '2022-04-17',
        ]);

        // Pernikahan David & Ester (otomatis create 2 member_sacraments 'nikah')
        $marriageDavidEster = Marriage::create([
            'church_id' => $candimas->id,
            'husband_member_id' => $mDavid->id,
            'wife_member_id' => $mEster->id,
            'marriage_date' => '2024-11-16',
            'official_id' => $pdtAndreas->id,
            'location' => 'Gedung Gereja Kelompok Candimas',
            'witness_names' => ['Hendra Wijaya', 'Santoso Surya'],
            'program_id' => $progNikah->id,
            'certificate_number' => 'MN-2024-0005',
            'issued_at' => '2024-11-16',
            'notes' => 'Pemberkatan nikah kudus dipimpin oleh Pdt. Andreas Nugroho, M.Th.',
        ]);
        $marriageDavidEster->syncSacraments();

        // Pernikahan Santoso & Maria
        $marriageSantosoMaria = Marriage::create([
            'church_id' => $candimas->id,
            'husband_member_id' => $mSantoso->id,
            'wife_member_id' => $mMaria->id,
            'marriage_date' => '2007-06-20',
            'official_id' => $pdtAndreas->id,
            'location' => 'Gedung Gereja Kelompok Candimas',
            'witness_names' => ['Yohanes Pranoto', 'Budi Setyawan'],
            'certificate_number' => 'MN-2007-0012',
            'issued_at' => '2007-06-20',
        ]);
        $marriageSantosoMaria->syncSacraments();

        // Catatan Kematian
        DeathRecord::create([
            'church_id' => $candimas->id,
            'member_id' => $mElisabeth->id,
            'death_date' => '2025-10-04',
            'burial_date' => '2025-10-06',
            'burial_location' => 'TPU Kristen Candimas, Natar',
            'official_id' => $pdtAndreas->id,
            'certificate_number' => 'SKM-2025-0003',
            'issued_at' => '2025-10-07',
            'notes' => 'Tutup usia dalam damai sejahtera Kristus.',
        ]);

        // 11. Jadwal Berulang (Recurring Schedules)
        $scheduleIbadahMinggu = RecurringSchedule::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Raya Minggu']->id,
            'title' => 'Ibadah Raya Minggu Pagi',
            'description' => 'Ibadah rutin jemaat setiap hari Minggu jam 09:00 WIB',
            'location' => 'Gedung Gereja Utama Candimas',
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [0], // Minggu
            'start_date' => '2026-01-01',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'end_type' => 'until_date',
            'until_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $schedulePemuda = RecurringSchedule::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Pemuda & Remaja']->id,
            'title' => 'Persekutuan Pemuda & Remaja',
            'description' => 'Ibadah persekutuan kaum muda setiap hari Sabtu sore',
            'location' => 'Ruang Pemuda Candimas',
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => [6], // Sabtu
            'start_date' => '2026-01-01',
            'start_time' => '17:00:00',
            'end_time' => '19:00:00',
            'end_type' => 'until_date',
            'until_date' => '2026-12-31',
            'is_active' => true,
        ]);

        // 12. Acara Ibadah & Kegiatan (Past & Upcoming Events)
        // Event 1 (Past)
        $ev1 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Raya Minggu']->id,
            'recurring_schedule_id' => $scheduleIbadahMinggu->id,
            'title' => 'Ibadah Raya Minggu XIII Trinitatis',
            'start_datetime' => '2026-08-30 09:00:00',
            'end_datetime' => '2026-08-30 11:00:00',
            'location' => 'Gedung Gereja Utama Candimas',
            'attendance_male' => 44,
            'attendance_female' => 50,
        ]);

        // Event 2 (Past)
        $ev2 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Raya Minggu']->id,
            'recurring_schedule_id' => $scheduleIbadahMinggu->id,
            'title' => 'Ibadah Raya Minggu XIV Trinitatis',
            'start_datetime' => '2026-09-06 09:00:00',
            'end_datetime' => '2026-09-06 11:00:00',
            'location' => 'Gedung Gereja Utama Candimas',
            'attendance_male' => 48,
            'attendance_female' => 54,
        ]);

        // Event 3 (Past)
        $ev3 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Persekutuan Doa & PA']->id,
            'title' => 'Persekutuan Doa Tengah Minggu',
            'start_datetime' => '2026-09-09 19:00:00',
            'end_datetime' => '2026-09-09 20:30:00',
            'location' => 'Ruang Konsistori Candimas',
            'attendance_male' => 12,
            'attendance_female' => 16,
        ]);

        // Event 4 (Past)
        $ev4 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Pemuda & Remaja']->id,
            'recurring_schedule_id' => $schedulePemuda->id,
            'title' => 'Persekutuan Pemuda "Hidup Berakar dalam Kasih"',
            'start_datetime' => '2026-09-12 17:00:00',
            'end_datetime' => '2026-09-12 19:00:00',
            'location' => 'Ruang Pemuda Candimas',
            'attendance_male' => 15,
            'attendance_female' => 19,
        ]);

        // Event 5 (Past / Minggu Terakhir)
        $ev5 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Raya Minggu']->id,
            'recurring_schedule_id' => $scheduleIbadahMinggu->id,
            'title' => 'Ibadah Raya Minggu XV Trinitatis',
            'start_datetime' => '2026-09-13 09:00:00',
            'end_datetime' => '2026-09-13 11:00:00',
            'location' => 'Gedung Gereja Utama Candimas',
            'attendance_male' => 52,
            'attendance_female' => 58,
        ]);

        // Event 6 (Upcoming - Sabtu depan)
        $ev6 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Pemuda & Remaja']->id,
            'recurring_schedule_id' => $schedulePemuda->id,
            'title' => 'Persekutuan Pemuda "Kasih yang Memulihkan"',
            'start_datetime' => '2026-09-19 17:00:00',
            'end_datetime' => '2026-09-19 19:00:00',
            'location' => 'Ruang Pemuda Candimas',
        ]);

        // Event 7 (Upcoming - Minggu depan)
        $ev7 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Raya Minggu']->id,
            'recurring_schedule_id' => $scheduleIbadahMinggu->id,
            'title' => 'Ibadah Raya Minggu XVI & Perjamuan Kudus',
            'start_datetime' => '2026-09-20 09:00:00',
            'end_datetime' => '2026-09-20 11:00:00',
            'location' => 'Gedung Gereja Utama Candimas',
        ]);

        // Event 8 (Upcoming - Doa Sektor)
        $ev8 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Persekutuan Doa & PA']->id,
            'title' => 'Persekutuan Doa Wilayah Candimas Barat',
            'start_datetime' => '2026-09-23 19:00:00',
            'end_datetime' => '2026-09-23 20:30:00',
            'location' => 'Kediaman Kel. Santoso Surya',
        ]);

        // Event 9 (Upcoming)
        $ev9 = Event::create([
            'church_id' => $candimas->id,
            'category_id' => $categories['Ibadah Raya Minggu']->id,
            'recurring_schedule_id' => $scheduleIbadahMinggu->id,
            'title' => 'Ibadah Raya Minggu XVII Trinitatis',
            'start_datetime' => '2026-09-27 09:00:00',
            'end_datetime' => '2026-09-27 11:00:00',
            'location' => 'Gedung Gereja Utama Candimas',
        ]);

        // 13. Petugas Ibadah (Event Rosters)
        // Petugas Event 5 (2026-09-13)
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'role_id' => $roles['Pendeta']->id,
            'official_id' => $pdtAndreas->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'role_id' => $roles['Penatua / Majelis']->id,
            'official_id' => $officialHendra->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'role_id' => $roles['Pemusik / Organis']->id,
            'member_id' => $mSamuel->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'role_id' => $roles['Pemimpin Pujian (Worship Leader)']->id,
            'member_id' => $mDavid->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'role_id' => $roles['Singer']->id,
            'member_id' => $mMaria->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'role_id' => $roles['Penerima Tamu (Usher)']->id,
            'member_id' => $mSantoso->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'role_id' => $roles['Operator Multimedia & Sound']->id,
            'member_id' => $mJonathan->id,
        ]);

        // Petugas Event 7 (Upcoming 2026-09-20 - Perjamuan Kudus)
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev7->id,
            'role_id' => $roles['Pendeta']->id,
            'official_id' => $pdtStephen->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev7->id,
            'role_id' => $roles['Diaken']->id,
            'official_id' => $officialYohana->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev7->id,
            'role_id' => $roles['Pemusik / Organis']->id,
            'member_id' => $mSamuel->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev7->id,
            'role_id' => $roles['Pemimpin Pujian (Worship Leader)']->id,
            'member_id' => $mMaria->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev7->id,
            'role_id' => $roles['Singer']->id,
            'member_id' => $mEster->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev7->id,
            'role_id' => $roles['Penerima Tamu (Usher)']->id,
            'member_id' => $mSantoso->id,
        ]);

        // Petugas Event 4 (Pemuda 2026-09-12)
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev4->id,
            'role_id' => $roles['Pemimpin Pujian (Worship Leader)']->id,
            'member_id' => $mDavid->id,
        ]);
        EventRoster::create([
            'church_id' => $candimas->id,
            'event_id' => $ev4->id,
            'role_id' => $roles['Pemusik / Organis']->id,
            'member_id' => $mSamuel->id,
        ]);

        // 14. Kehadiran Ibadah (Event Attendances)
        $attendeesEv5 = [$mSantoso, $mMaria, $mDaniel, $mHendra, $mYohana, $mSamuel, $mMarkus, $mRuth, $mDavid, $mEster, $mJonathan];
        foreach ($attendeesEv5 as $attMember) {
            EventAttendance::checkInOrRestore([
                'church_id' => $candimas->id,
                'event_id' => $ev5->id,
                'member_id' => $attMember->id,
                'status' => 'hadir',
                'notes' => 'Hadir ibadah minggu',
            ]);
        }

        $attendeesEv2 = [$mSantoso, $mMaria, $mDaniel, $mHendra, $mSamuel, $mDavid, $mEster];
        foreach ($attendeesEv2 as $attMember) {
            EventAttendance::checkInOrRestore([
                'church_id' => $candimas->id,
                'event_id' => $ev2->id,
                'member_id' => $attMember->id,
                'status' => 'hadir',
            ]);
        }

        // 15. Notulen Rapat (Meeting Minutes)
        MeetingMinutes::create([
            'church_id' => $candimas->id,
            'event_id' => $ev5->id,
            'title' => 'Notulen Rapat Majelis Bulanan — September 2026',
            'meeting_date' => '2026-09-13',
            'agenda' => "1. Evaluasi pelayanan ibadah bulan Agustus 2026.\n2. Persiapan Sakramen Perjamuan Kudus Minggu 20 September 2026.\n3. Laporan keuangan dan progres pemeliharaan pastori.",
            'participants' => 'Pdt. Andreas Nugroho, Bpk. Hendra Wijaya, Ibu Yohana Wijaya, Bpk. Santoso Surya',
            'notes' => 'Rapat dibuka dengan doa oleh Pdt. Andreas Nugroho dan berlangsung dengan penuh suasana kekeluargaan.',
            'decisions' => "1. Menyetujui pelaksanaan Perjamuan Kudus pada 20 September 2026 dipimpin Pdt. Dr. Stephen Wong.\n2. Anggaran pemeliharaan AC disetujui sebesar Rp 1.200.000 dari Kas Operasional.\n3. Jadwal bimbingan pra-sidi tetap berjalan setiap hari Sabtu.",
        ]);

        // 16. Transaksi Keuangan (Transactions)
        $funds = Fund::where('church_id', $candimas->id)->get()->keyBy('name');
        $fOperasional = $funds['Kas Operasional'] ?? Fund::firstOrCreate(['church_id' => $candimas->id, 'name' => 'Kas Operasional']);
        $fPembangunan = $funds['Kas Pembangunan'] ?? Fund::firstOrCreate(['church_id' => $candimas->id, 'name' => 'Kas Pembangunan']);
        $fDiakonia = $funds['Kas Diakonia'] ?? Fund::firstOrCreate(['church_id' => $candimas->id, 'name' => 'Kas Diakonia']);
        $fMisi = $funds['Kas Misi'] ?? Fund::firstOrCreate(['church_id' => $candimas->id, 'name' => 'Kas Misi']);

        $cats = FinancialCategory::where('church_id', $candimas->id)->get()->keyBy('name');

        $transactionsData = [
            // Kas Operasional - Debit (Pemasukan)
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Kolekte Ibadah Raya']?->id,
                'type' => 'debit',
                'amount' => 2450000,
                'transaction_date' => '2026-08-30',
                'description' => 'Kolekte Ibadah Raya Minggu XIII Trinitatis',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Persepuluhan']?->id,
                'type' => 'debit',
                'amount' => 8500000,
                'transaction_date' => '2026-09-01',
                'description' => 'Persepuluhan Jemaat Periode September 2026',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Kolekte Ibadah Raya']?->id,
                'type' => 'debit',
                'amount' => 2780000,
                'transaction_date' => '2026-09-06',
                'description' => 'Kolekte Ibadah Raya Minggu XIV Trinitatis',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Persembahan Syukur']?->id,
                'type' => 'debit',
                'amount' => 1500000,
                'transaction_date' => '2026-09-08',
                'description' => 'Persembahan Syukur Keluarga Santoso Surya',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Kolekte Ibadah Raya']?->id,
                'type' => 'debit',
                'amount' => 3120000,
                'transaction_date' => '2026-09-13',
                'description' => 'Kolekte Ibadah Raya Minggu XV Trinitatis',
            ],

            // Kas Operasional - Credit (Pengeluaran)
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Operasional Utilitas (Listrik/Air)']?->id,
                'type' => 'credit',
                'amount' => 1850000,
                'transaction_date' => '2026-09-02',
                'description' => 'Pembayaran Tagihan Listrik PLN & PDAM Gereja',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Gaji & Honorarium']?->id,
                'type' => 'credit',
                'amount' => 3500000,
                'transaction_date' => '2026-09-05',
                'description' => 'Honorarium Koster, Tenaga Kebersihan, dan Staf TU',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Administrasi & ATK']?->id,
                'type' => 'credit',
                'amount' => 450000,
                'transaction_date' => '2026-09-07',
                'description' => 'Pengadaan Kertas HVS & Tinta Cetak Warta Jemaat',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Pemeliharaan Aset']?->id,
                'type' => 'credit',
                'amount' => 1200000,
                'transaction_date' => '2026-09-10',
                'description' => 'Servis Berkala AC Ruang Ibadah & Perbaikan Mic Wireless',
            ],
            [
                'fund_id' => $fOperasional->id,
                'category_id' => $cats['Konsumsi']?->id,
                'type' => 'credit',
                'amount' => 350000,
                'transaction_date' => '2026-09-13',
                'description' => 'Konsumsi & Snack Pelayan Ibadah dan Rapat Majelis',
            ],

            // Kas Pembangunan
            [
                'fund_id' => $fPembangunan->id,
                'category_id' => $cats['Persembahan Pembangunan']?->id,
                'type' => 'debit',
                'amount' => 15000000,
                'transaction_date' => '2026-08-25',
                'description' => 'Lelang Donasi & Komitmen Pembangunan Ruang Konsistori',
            ],
            [
                'fund_id' => $fPembangunan->id,
                'category_id' => $cats['Persembahan Pembangunan']?->id,
                'type' => 'debit',
                'amount' => 4250000,
                'transaction_date' => '2026-09-06',
                'description' => 'Kotak Pembangunan Minggu ke-1 September 2026',
            ],
            [
                'fund_id' => $fPembangunan->id,
                'category_id' => $cats['Pemeliharaan Aset']?->id,
                'type' => 'credit',
                'amount' => 8750000,
                'transaction_date' => '2026-09-10',
                'description' => 'Pembelian Semen, Keramik, dan Material Renovasi Konsistori',
            ],
            [
                'fund_id' => $fPembangunan->id,
                'category_id' => $cats['Persembahan Pembangunan']?->id,
                'type' => 'debit',
                'amount' => 3800000,
                'transaction_date' => '2026-09-13',
                'description' => 'Kotak Pembangunan Minggu ke-2 September 2026',
            ],

            // Kas Diakonia
            [
                'fund_id' => $fDiakonia->id,
                'category_id' => $cats['Persembahan Diakonia']?->id,
                'type' => 'debit',
                'amount' => 1650000,
                'transaction_date' => '2026-09-06',
                'description' => 'Persembahan Kantong Merah (Diakonia) Minggu ke-1',
            ],
            [
                'fund_id' => $fDiakonia->id,
                'category_id' => $cats['Bantuan Diakonia']?->id,
                'type' => 'credit',
                'amount' => 1500000,
                'transaction_date' => '2026-09-08',
                'description' => 'Bantuan Santunan Rawat Inap Warga Jemaat Lansia',
            ],
            [
                'fund_id' => $fDiakonia->id,
                'category_id' => $cats['Bantuan Diakonia']?->id,
                'type' => 'credit',
                'amount' => 1200000,
                'transaction_date' => '2026-09-11',
                'description' => 'Pengadaan 10 Paket Sembako Kasih Warga Prasejahtera',
            ],
            [
                'fund_id' => $fDiakonia->id,
                'category_id' => $cats['Persembahan Diakonia']?->id,
                'type' => 'debit',
                'amount' => 1820000,
                'transaction_date' => '2026-09-13',
                'description' => 'Persembahan Kantong Merah (Diakonia) Minggu ke-2',
            ],

            // Kas Misi
            [
                'fund_id' => $fMisi->id,
                'category_id' => $cats['Lain-lain']?->id,
                'type' => 'debit',
                'amount' => 2000000,
                'transaction_date' => '2026-09-01',
                'description' => 'Alokasi Subsidi Kas Misi Bulanan',
            ],
            [
                'fund_id' => $fMisi->id,
                'category_id' => $cats['Misi & Penginjilan']?->id,
                'type' => 'credit',
                'amount' => 1500000,
                'transaction_date' => '2026-09-12',
                'description' => 'Bantuan Operasional Pos Pelayanan Injil Desa Sukamaju',
            ],
        ];

        foreach ($transactionsData as $tData) {
            if ($tData['category_id'] !== null) {
                Transaction::create(array_merge($tData, ['church_id' => $candimas->id]));
            }
        }

        // 17. Warta Jemaat Terpublikasi (Warta Publications)
        // Edisi 1: Current / Live Edisi Minggu 14 - 20 September 2026
        WartaPublication::create([
            'church_id' => $candimas->id,
            'title' => 'Warta Jemaat — Minggu XVI Setelah Trinitatis',
            'period_start' => '2026-09-14',
            'period_end' => '2026-09-20',
            'status' => 'published',
            'published_at' => Carbon::parse('2026-09-14 06:00:00'),
            'created_by' => $adminCandimas->id,
            'content' => [
                'church' => [
                    'name' => $candimas->name,
                    'synod' => $candimas->synod,
                    'address' => $candimas->address,
                    'phone' => $candimas->phone,
                    'email' => $candimas->email,
                    'website' => $candimas->website,
                    'logo_url' => $candimas->logo_url,
                ],
                'church_name' => $candimas->name,
                'church_address' => $candimas->address,
                'period_label' => '14 September 2026 – 20 September 2026',
                'edition_label' => 'Tahun Pelayanan 2026 • No. 38',
                'reflection' => "“Segala perkara dapat kutanggung di dalam Dia yang memberi kekuatan kepadaku.” (Filipi 4:13)\n\nMarilah kita menjalani minggu ini dengan penuh pengharapan dan kesetiaan melayani Tuhan di tengah keluarga dan pekerjaan kita.",
                'events' => [
                    [
                        'name' => 'Ibadah Raya Minggu XVI & Perjamuan Kudus',
                        'start' => '20/09/2026 09:00',
                        'location' => 'Gedung Gereja Utama Candimas',
                        'officials' => 'Pdt. Dr. Stephen Wong (Pelayan Tamu), Ibu Yohana Wijaya (Diaken), Sdr. Samuel Wijaya (Pemusik)',
                    ],
                    [
                        'name' => 'Persekutuan Pemuda "Kasih yang Memulihkan"',
                        'start' => '19/09/2026 17:00',
                        'location' => 'Ruang Pemuda Candimas',
                        'officials' => 'Sdr. David Christian, Sdr. Samuel Wijaya',
                    ],
                    [
                        'name' => 'Persekutuan Doa Wilayah Candimas Barat',
                        'start' => '23/09/2026 19:00',
                        'location' => 'Kediaman Kel. Santoso Surya',
                        'officials' => 'Bpk. Hendra Wijaya',
                    ],
                ],
                'birthdays' => [
                    ['name' => 'Daniel Surya', 'date' => '15/09/2008'],
                    ['name' => 'Ester Natalia', 'date' => '17/09/1998'],
                    ['name' => 'Susana Setyawan', 'date' => '15/10/1979'],
                ],
                'sacraments' => [
                    [
                        'date' => '20/09/2026',
                        'type' => 'Perjamuan Kudus',
                        'name' => 'Seluruh Jemaat yang Telah Mengaku Percaya (Sidi)',
                        'official' => 'Pdt. Dr. Stephen Wong',
                    ],
                    [
                        'date' => '15/06/2025',
                        'type' => 'Baptis Anak',
                        'name' => 'Timothy Sihombing',
                        'official' => 'Pdt. Andreas Nugroho, M.Th.',
                    ],
                ],
                'finance' => [
                    'opening_balance' => 22450000,
                    'total_income' => 17650000,
                    'total_expenses' => 7350000,
                    'closing_balance' => 32750000,
                    'funds' => [
                        [
                            'id' => 1,
                            'name' => 'Kas Operasional (Kantong 1)',
                            'opening_balance' => 12000000,
                            'income' => [
                                'total' => 11500000,
                                'items' => [
                                    ['category' => 'Kolekte Ibadah Raya', 'amount' => 6000000],
                                    ['category' => 'Persepuluhan', 'amount' => 4000000],
                                    ['category' => 'Persembahan Syukur', 'amount' => 1500000],
                                ],
                            ],
                            'expense' => [
                                'total' => 4650000,
                                'items' => [
                                    ['category' => 'Operasional Utilitas (Listrik/Air)', 'amount' => 1650000],
                                    ['category' => 'Administrasi & ATK', 'amount' => 500000],
                                    ['category' => 'Gaji & Honorarium', 'amount' => 2500000],
                                ],
                            ],
                            'closing_balance' => 18850000,
                        ],
                        [
                            'id' => 2,
                            'name' => 'Kas Pembangunan (Kantong 2)',
                            'opening_balance' => 6250000,
                            'income' => [
                                'total' => 3800000,
                                'items' => [
                                    ['category' => 'Persembahan Pembangunan', 'amount' => 3800000],
                                ],
                            ],
                            'expense' => [
                                'total' => 1200000,
                                'items' => [
                                    ['category' => 'Pemeliharaan Aset', 'amount' => 1200000],
                                ],
                            ],
                            'closing_balance' => 8850000,
                        ],
                        [
                            'id' => 3,
                            'name' => 'Kas Diakonia (Kantong 3)',
                            'opening_balance' => 3200000,
                            'income' => [
                                'total' => 1850000,
                                'items' => [
                                    ['category' => 'Persembahan Diakonia', 'amount' => 1850000],
                                ],
                            ],
                            'expense' => [
                                'total' => 1000000,
                                'items' => [
                                    ['category' => 'Bantuan Diakonia', 'amount' => 1000000],
                                ],
                            ],
                            'closing_balance' => 4050000,
                        ],
                        [
                            'id' => 4,
                            'name' => 'Kas Misi (Kantong 4)',
                            'opening_balance' => 1000000,
                            'income' => [
                                'total' => 500000,
                                'items' => [
                                    ['category' => 'Persembahan Kategorial', 'amount' => 500000],
                                ],
                            ],
                            'expense' => [
                                'total' => 500000,
                                'items' => [
                                    ['category' => 'Misi & Penginjilan', 'amount' => 500000],
                                ],
                            ],
                            'closing_balance' => 1000000,
                        ],
                    ],
                ],
                'announcements' => [
                    [
                        'title' => 'Pelaksanaan Sakramen Perjamuan Kudus',
                        'body' => 'Sakramen Perjamuan Kudus triwulan ketiga akan dilayankan pada kebaktian hari Minggu, 20 September 2026 pukul 09:00 WIB. Jemaat dimohon mempersiapkan hati.',
                    ],
                    [
                        'title' => 'Aksi Kasih Diakonia Sembako',
                        'body' => 'Komisi Diakonia membuka kesempatan bagi jemaat yang terbeban menyumbang bahan pokok bagi warga sekitar gereja. Bantuan dapat diserahkan melalui Majelis.',
                    ],
                ],
            ],
        ]);

        // Edisi 2: Past Edisi Minggu 07 - 13 September 2026
        WartaPublication::create([
            'church_id' => $candimas->id,
            'title' => 'Warta Jemaat — Minggu XV Setelah Trinitatis',
            'period_start' => '2026-09-07',
            'period_end' => '2026-09-13',
            'status' => 'published',
            'published_at' => Carbon::parse('2026-09-07 06:00:00'),
            'created_by' => $adminCandimas->id,
            'content' => [
                'church' => [
                    'name' => $candimas->name,
                    'synod' => $candimas->synod,
                    'address' => $candimas->address,
                    'phone' => $candimas->phone,
                    'email' => $candimas->email,
                    'website' => $candimas->website,
                    'logo_url' => $candimas->logo_url,
                ],
                'church_name' => $candimas->name,
                'church_address' => $candimas->address,
                'period_label' => '07 September 2026 – 13 September 2026',
                'edition_label' => 'Tahun Pelayanan 2026 • No. 37',
                'reflection' => "“Kasihilah sesamamu manusia seperti dirimu sendiri.” (Matius 22:39)",
                'events' => [
                    [
                        'name' => 'Ibadah Raya Minggu XV Trinitatis',
                        'start' => '13/09/2026 09:00',
                        'location' => 'Gedung Gereja Utama Candimas',
                        'officials' => 'Pdt. Andreas Nugroho, M.Th., Bpk. Hendra Wijaya',
                    ],
                    [
                        'name' => 'Persekutuan Doa Tengah Minggu',
                        'start' => '09/09/2026 19:00',
                        'location' => 'Ruang Konsistori Candimas',
                        'officials' => 'Pdt. Andreas Nugroho',
                    ],
                ],
                'birthdays' => [
                    ['name' => 'Samuel Wijaya', 'date' => '12/09/2004'],
                    ['name' => 'Elisabeth Pranoto', 'date' => '08/09/1955'],
                ],
                'sacraments' => [],
                'finance' => [
                    'opening_balance' => 16500000,
                    'total_income' => 14200000,
                    'total_expenses' => 8250000,
                    'closing_balance' => 22450000,
                    'funds' => [
                        [
                            'id' => 1,
                            'name' => 'Kas Operasional (Kantong 1)',
                            'opening_balance' => 9000000,
                            'income' => [
                                'total' => 8500000,
                                'items' => [
                                    ['category' => 'Kolekte Ibadah Raya', 'amount' => 5000000],
                                    ['category' => 'Persepuluhan', 'amount' => 3500000],
                                ],
                            ],
                            'expense' => [
                                'total' => 5500000,
                                'items' => [
                                    ['category' => 'Gaji & Honorarium', 'amount' => 3500000],
                                    ['category' => 'Operasional Utilitas (Listrik/Air)', 'amount' => 2000000],
                                ],
                            ],
                            'closing_balance' => 12000000,
                        ],
                        [
                            'id' => 2,
                            'name' => 'Kas Pembangunan (Kantong 2)',
                            'opening_balance' => 4500000,
                            'income' => [
                                'total' => 3250000,
                                'items' => [
                                    ['category' => 'Persembahan Pembangunan', 'amount' => 3250000],
                                ],
                            ],
                            'expense' => [
                                'total' => 1500000,
                                'items' => [
                                    ['category' => 'Pemeliharaan Aset', 'amount' => 1500000],
                                ],
                            ],
                            'closing_balance' => 6250000,
                        ],
                        [
                            'id' => 3,
                            'name' => 'Kas Diakonia (Kantong 3)',
                            'opening_balance' => 2000000,
                            'income' => [
                                'total' => 1950000,
                                'items' => [
                                    ['category' => 'Persembahan Diakonia', 'amount' => 1950000],
                                ],
                            ],
                            'expense' => [
                                'total' => 750000,
                                'items' => [
                                    ['category' => 'Bantuan Diakonia', 'amount' => 750000],
                                ],
                            ],
                            'closing_balance' => 3200000,
                        ],
                        [
                            'id' => 4,
                            'name' => 'Kas Misi (Kantong 4)',
                            'opening_balance' => 1000000,
                            'income' => [
                                'total' => 500000,
                                'items' => [
                                    ['category' => 'Lain-lain', 'amount' => 500000],
                                ],
                            ],
                            'expense' => [
                                'total' => 500000,
                                'items' => [
                                    ['category' => 'Misi & Penginjilan', 'amount' => 500000],
                                ],
                            ],
                            'closing_balance' => 1000000,
                        ],
                    ],
                ],
                'announcements' => [
                    [
                        'title' => 'Kerja Bakti Lingkungan Gereja',
                        'body' => 'Kerja bakti pembersihan ruang ibadah dan halaman gereja akan diadakan hari Sabtu pagi pukul 07.30 WIB.',
                    ],
                ],
            ],
        ]);

        // Edisi 3: Warta untuk Trimulyo (agar pemilih gereja di /warta multi-tenant berfungsi optimal)
        WartaPublication::create([
            'church_id' => $trimulyo->id,
            'title' => 'Warta Jemaat Trimulyo — Edisi September 2026',
            'period_start' => '2026-09-14',
            'period_end' => '2026-09-20',
            'status' => 'published',
            'published_at' => Carbon::parse('2026-09-14 06:00:00'),
            'created_by' => $adminTrimulyo->id,
            'content' => [
                'church' => [
                    'name' => $trimulyo->name,
                    'synod' => $trimulyo->synod,
                    'address' => $trimulyo->address,
                    'phone' => $trimulyo->phone,
                    'email' => $trimulyo->email,
                    'website' => $trimulyo->website,
                    'logo_url' => $trimulyo->logo_url,
                ],
                'church_name' => $trimulyo->name,
                'church_address' => $trimulyo->address,
                'period_label' => '14 September 2026 – 20 September 2026',
                'edition_label' => 'Tahun Pelayanan 2026 • No. 12',
                'reflection' => "“Tuhan adalah gembalaku, takkan kekurangan aku.” (Mazmur 23:1)",
                'events' => [
                    [
                        'name' => 'Ibadah Raya Minggu Pagi',
                        'start' => '20/09/2026 08:30',
                        'location' => 'Gedung Gereja Trimulyo',
                        'officials' => 'Pdt. Andreas Nugroho, M.Th.',
                    ],
                ],
                'birthdays' => [],
                'sacraments' => [],
                'finance' => [
                    'opening_balance' => 12000000,
                    'total_income' => 4500000,
                    'total_expenses' => 2100000,
                    'closing_balance' => 14400000,
                    'funds' => [
                        [
                            'id' => 1,
                            'name' => 'Kas Operasional (Kantong 1)',
                            'opening_balance' => 8000000,
                            'income' => [
                                'total' => 3000000,
                                'items' => [
                                    ['category' => 'Kolekte Ibadah Raya', 'amount' => 2000000],
                                    ['category' => 'Persembahan Syukur', 'amount' => 1000000],
                                ],
                            ],
                            'expense' => [
                                'total' => 1500000,
                                'items' => [
                                    ['category' => 'Operasional Utilitas (Listrik/Air)', 'amount' => 1500000],
                                ],
                            ],
                            'closing_balance' => 9500000,
                        ],
                        [
                            'id' => 2,
                            'name' => 'Kas Pembangunan (Kantong 2)',
                            'opening_balance' => 2500000,
                            'income' => [
                                'total' => 1000000,
                                'items' => [
                                    ['category' => 'Persembahan Pembangunan', 'amount' => 1000000],
                                ],
                            ],
                            'expense' => [
                                'total' => 400000,
                                'items' => [
                                    ['category' => 'Pemeliharaan Aset', 'amount' => 400000],
                                ],
                            ],
                            'closing_balance' => 3100000,
                        ],
                        [
                            'id' => 3,
                            'name' => 'Kas Diakonia (Kantong 3)',
                            'opening_balance' => 1500000,
                            'income' => [
                                'total' => 500000,
                                'items' => [
                                    ['category' => 'Persembahan Diakonia', 'amount' => 500000],
                                ],
                            ],
                            'expense' => [
                                'total' => 200000,
                                'items' => [
                                    ['category' => 'Bantuan Diakonia', 'amount' => 200000],
                                ],
                            ],
                            'closing_balance' => 1800000,
                        ],
                    ],
                ],
                'announcements' => [
                    [
                        'title' => 'Ibadah Keluarga Pekanan',
                        'body' => 'Ibadah keluarga sektor utara akan diadakan hari Rabu pukul 19:00 WIB.',
                    ],
                ],
            ],
        ]);
    }
}
