<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Church;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi email jadwal ibadah mendatang (Task slot sore — Notifikasi email).
 *
 * Dikirim oleh command `warta:notify-schedule` ke church_admin gereja terkait
 * (jadwal 05:30) berisi acara ibadah 7 hari ke depan.
 *
 * @phpstan-type ScheduleItem array{title: string, start: string|null, location: string|null}
 */
class WorshipScheduleNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<ScheduleItem>  $events
     */
    public function __construct(
        public readonly Church $church,
        public readonly array $events,
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
        $lines = collect($this->events)
            ->map(function (array $e): string {
                $when = $e['start'] !== null ? $e['start'] : 'jadwal menyusul';
                $where = $e['location'] !== null ? " ({$e['location']})" : '';

                return "• {$e['title']} — {$when}{$where}";
            })
            ->all();

        return (new MailMessage)
            ->subject("[Portal Gereja] Jadwal ibadah mendatang — {$this->church->name}")
            ->greeting('Yth. Admin Gereja,')
            ->line('Berikut jadwal ibadah/acara gereja 7 hari ke depan:')
            ->lines($lines)
            ->line('Silakan siapkan pelayanan dan informasi untuk jemaat.')
            ->salutation('Sistem Portal Gereja');
    }
}
