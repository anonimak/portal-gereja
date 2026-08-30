<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserEscalationTest extends TestCase
{
    use RefreshDatabase;

    private Church $churchA;
    private Church $churchB;
    private User $adminA;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::factory()->create();
        $this->churchB = Church::factory()->create();
        $this->adminA = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);
        $this->superAdmin = User::factory()->create([
            'church_id' => $this->churchA->id,
            'role' => 'super_admin',
        ]);
    }

    public function test_church_admin_tidak_bisa_membuat_super_admin(): void
    {
        $this->actingAs($this->adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Tidak diizinkan membuat user Super Admin.');

        User::create([
            'name' => 'Nakal',
            'email' => 'nakal@test.dev',
            'password' => 'secret',
            'church_id' => $this->churchA->id,
            'role' => 'super_admin',
        ]);
    }

    public function test_church_admin_tidak_bisa_membuat_user_gereja_lain(): void
    {
        $this->actingAs($this->adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Tidak diizinkan membuat user untuk gereja lain.');

        User::create([
            'name' => 'Nakal',
            'email' => 'nakal2@test.dev',
            'password' => 'secret',
            'church_id' => $this->churchB->id,
            'role' => 'church_admin',
        ]);
    }

    public function test_role_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("Role 'hacker' tidak valid.");

        User::create([
            'name' => 'Nakal',
            'email' => 'nakal3@test.dev',
            'password' => 'secret',
            'church_id' => $this->churchA->id,
            'role' => 'hacker',
        ]);
    }

    public function test_church_admin_tidak_bisa_mengubah_super_admin(): void
    {
        $this->actingAs($this->adminA);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Tidak diizinkan mengubah user Super Admin.');

        $this->superAdmin->update(['name' => 'Diubah']);
    }

    public function test_super_admin_tidak_bisa_menurunkan_role_sendiri(): void
    {
        $this->actingAs($this->superAdmin);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Super Admin tidak dapat menurunkan role dirinya sendiri.');

        $this->superAdmin->update(['role' => 'church_admin']);
    }

    public function test_super_admin_bisa_membuat_church_admin(): void
    {
        $this->actingAs($this->superAdmin);

        $user = User::create([
            'name' => 'Baru',
            'email' => 'baru@test.dev',
            'password' => 'secret',
            'church_id' => $this->churchA->id,
            'role' => 'church_admin',
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'church_admin']);
    }

    public function test_user_policy_hanya_super_admin(): void
    {
        Gate::forUser($this->adminA)->denies('viewAny', User::class);
        Gate::forUser($this->adminA)->denies('update', $this->superAdmin);
        Gate::forUser($this->superAdmin)->allows('viewAny', User::class);
        Gate::forUser($this->superAdmin)->allows('update', $this->adminA);
        Gate::forUser($this->superAdmin)->denies('delete', $this->superAdmin);
    }
}
