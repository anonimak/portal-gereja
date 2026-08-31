<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * Resolver gereja aktif (Fase 3A §9).
 *
 * - super_admin: memilih satu gereja (session `active_church_id`) ATAU "All" (null).
 * - role lain: selalu gereja sendiri (isolasi tenant — tidak berubah).
 */
final class ChurchContext
{
    public const SESSION_KEY = 'active_church_id';

    /**
     * Gereja aktif untuk query saat ini.
     * null = All (hanya untuk super_admin).
     */
    public static function activeChurchId(?User $user = null): ?int
    {
        $user ??= auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->role !== 'super_admin') {
            return (int) $user->church_id;
        }

        return self::sessionValue();
    }

    /**
     * Apakah context aktif "All" (super_admin tanpa pilihan gereja).
     */
    public static function isAll(?User $user = null): bool
    {
        return self::activeChurchId($user) === null;
    }

    /**
     * Set pilihan gereja aktif (hanya super_admin yang boleh).
     */
    public static function setActiveChurch(?int $churchId, ?User $user = null): void
    {
        $user ??= auth()->user();

        if (! $user || $user->role !== 'super_admin') {
            return;
        }

        try {
            Session::put(self::SESSION_KEY, $churchId);
        } catch (\Throwable) {
            // Session tidak tersedia (console/test) — abaikan.
        }
    }

    /**
     * Nama gereja aktif untuk kop laporan.
     */
    public static function churchName(?User $user = null): string
    {
        $user ??= auth()->user();
        $churchId = self::activeChurchId($user);

        if ($churchId === null) {
            return 'Semua Gereja';
        }

        $church = \App\Models\Church::query()->withoutGlobalScopes()->find($churchId);

        return $church?->name ?? 'Gereja';
    }

    /**
     * Baca nilai session dengan aman (tanpa session aktif → null).
     */
    private static function sessionValue(): ?int
    {
        try {
            $value = Session::get(self::SESSION_KEY);

            return $value ? (int) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
