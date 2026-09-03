<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Church;
use App\Models\Member;
use App\Models\User;
use App\Notifications\BirthdayNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Kirim notifikasi email ulang tahun ke church_admin tiap gereja
 * untuk anggota aktif yang berulang tahun hari ini.
 *
 * Terjadwal via routes/console.php: `warta:notify-birthdays` dailyAt 06:00.
 * Berjalan tanpa konteks auth — query lintas gereja aman (global scope
 * BelongsToChurch hanya aktif saat ada user panel).
 */
class NotifyBirthdays extends Command
{
    protected $signature = 'warta:notify-birthdays';

    protected $description = 'Kirim email ulang tahun anggota aktif ke church_admin tiap gereja';

    public function handle(): int
    {
        $todayMd = now()->format('m-d');

        $members = Member::query()
            ->select('id', 'church_id', 'full_name', 'birth_date')
            ->where('status', 'aktif')
            ->whereNotNull('birth_date')
            ->get()
            ->filter(fn (Member $member): bool => $member->birth_date?->format('m-d') === $todayMd)
            ->groupBy('church_id');

        if ($members->isEmpty()) {
            $this->info('Tidak ada anggota berulang tahun hari ini.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($members as $churchId => $churchMembers) {
            $church = Church::query()->find($churchId);

            if ($church === null) {
                continue;
            }

            $admins = User::query()
                ->where('church_id', $churchId)
                ->where('role', 'church_admin')
                ->get();

            if ($admins->isEmpty()) {
                continue;
            }

            $birthdays = $churchMembers
                ->map(fn (Member $member): array => [
                    'name' => $member->full_name,
                    'date' => $member->birth_date?->format('d/m/Y') ?? '-',
                ])
                ->values()
                ->all();

            try {
                Notification::send($admins, new BirthdayNotification($church, $birthdays));
                $sent += $admins->count();
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim notifikasi ulang tahun', [
                    'church_id' => $churchId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Notifikasi ulang tahun terkirim ke {$sent} admin.");

        return self::SUCCESS;
    }
}
