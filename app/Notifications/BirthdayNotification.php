<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Church;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi email ulang tahun anggota (Task slot sore — Notifikasi email).
 *
 * Dikirim oleh command `warta:notify-birthdays` ke church_admin gereja terkait
 * setiap pagi (jadwal 06:00) untuk anggota aktif yang berulang tahun hari ini.
 *
 * @phpstan-type BirthdayItem array{name: string, date: string}
 */
class BirthdayNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<BirthdayItem>  $birthdays
     */
    public function __construct(
        public readonly Church $church,
        public readonly array $birthdays,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lines = collect($this->birthdays)
            ->map(fn (array $b): string => "• {$b['name']} — {$b['date']}")
            ->all();

        return (new MailMessage)
            ->subject("[Portal Gereja] Ulang tahun jemaat — {$this->church->name}")
            ->greeting('Yth. Admin Gereja,')
            ->line('Jemaat berikut berulang tahun hari ini:')
            ->lines($lines)
            ->line('Sampaikan ucapan berkat dari pihak gereja kepada jemaat.')
            ->salutation('Sistem Portal Gereja');
    }
}
