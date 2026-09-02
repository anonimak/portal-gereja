<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Support\RoleRegistry;
use App\Traits\RecordsAuditTrail;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, RecordsAuditTrail;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'church_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Belongs to church relationship.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Gerbang akses panel Filament (AC-T2-04 — BLOCK-2 Vera; AC-T3-01).
     *
     * 6 role panel yang sah: super_admin, church_admin, finance_admin,
     * jemaat_admin, warta_editor, report_viewer. Role tak dikenal ('reader', dll.)
     * DITOLAK.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, UserRole::panelRoles(), true);
    }

    /**
     * RBAC granular (Fase 2 Task 3): cek permission via RoleRegistry.
     *
     * Menerima string key ('member.view') atau enum Permission. super_admin
     * selalu true (wildcard).
     */
    public function hasPermission(string|\App\Enums\Permission $permission): bool
    {
        return RoleRegistry::has($this, $permission);
    }
}
