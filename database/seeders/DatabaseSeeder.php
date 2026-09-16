<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Church;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test churches
        $churches = [
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

        foreach ($churches as $churchData) {
            $church = Church::updateOrCreate(['code' => $churchData['code']], $churchData);
            // Manually call DefaultFinanceSeeder since WithoutModelEvents prevents observer from firing
            $this->call(DefaultFinanceSeeder::class, false, ['churchId' => $church->id]);
        }

        // Create admin users for each church
        $churchCandimas = Church::where('code', 'GKSBS-KEL-CDM')->first();
        $churchTrimulyo = Church::where('code', 'GKSBS-KEL-TRM')->first();
        $churchMargomulyo = Church::where('code', 'GKSBS-KEL-MRG')->first();

        User::firstOrCreate(
            ['email' => 'admin.candimas@gksbs-filadelfia.org'],
            [
                'name' => 'Admin Kelompok Candimas',
                'password' => bcrypt('password'),
                'church_id' => $churchCandimas->id,
                'role' => 'church_admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin.trimulyo@gksbs-filadelfia.org'],
            [
                'name' => 'Admin Kelompok Trimulyo',
                'password' => bcrypt('password'),
                'church_id' => $churchTrimulyo->id,
                'role' => 'church_admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin.margomulyo@gksbs-filadelfia.org'],
            [
                'name' => 'Admin Kelompok Margomulyo',
                'password' => bcrypt('password'),
                'church_id' => $churchMargomulyo->id,
                'role' => 'church_admin',
            ]
        );

        // Create super admin user (can see all churches)
        User::firstOrCreate(
            ['email' => 'superadmin@gereja.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'church_id' => $churchCandimas->id,
                'role' => 'super_admin',
            ]
        );

        // Seed dummy demo data
        $this->call(DummyDataSeeder::class);
    }
}
