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
                'address' => 'Jl. Test No. 123',
                'phone' => '081234567890',
            ],
            [
                'code' => 'GKSBS-KEL-TRM',
                'name' => 'Jemaat Kelompok Trimulyo',
                'address' => 'Jl. Test No. 456',
                'phone' => '081234567891',
            ],
            [
                'code' => 'GKSBS-KEL-MRG',
                'name' => 'Jemaat Kelompok Margomulyo',
                'address' => 'Jl. Test No. 789',
                'phone' => '081234567892',
            ],
        ];

        foreach ($churches as $churchData) {
            $church = Church::create($churchData);
            // Manually call DefaultFinanceSeeder since WithoutModelEvents prevents observer from firing
            $this->call(DefaultFinanceSeeder::class, false, ['churchId' => $church->id]);
        }

        // Create admin users for each church
        $churchCandimas = Church::where('code', 'GKSBS-KEL-CDM')->first();
        $churchTrimulyo = Church::where('code', 'GKSBS-KEL-TRM')->first();
        $churchMargomulyo = Church::where('code', 'GKSBS-KEL-MRG')->first();

        User::create([
            'name' => 'Admin Kelompok Candimas',
            'email' => 'admin.candimas@gksbs-filadelfia.org',
            'password' => bcrypt('password'),
            'church_id' => $churchCandimas->id,
            'role' => 'church_admin',
        ]);

        User::create([
            'name' => 'Admin Kelompok Trimulyo',
            'email' => 'admin.trimulyo@gksbs-filadelfia.org',
            'password' => bcrypt('password'),
            'church_id' => $churchTrimulyo->id,
            'role' => 'church_admin',
        ]);

        User::create([
            'name' => 'Admin Kelompok Margomulyo',
            'email' => 'admin.margomulyo@gksbs-filadelfia.org',
            'password' => bcrypt('password'),
            'church_id' => $churchMargomulyo->id,
            'role' => 'church_admin',
        ]);

        // Create super admin user (can see all churches)
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gereja.test',
            'password' => bcrypt('password'),
            'church_id' => $churchCandimas->id,
            'role' => 'super_admin',
        ]);
    }
}
