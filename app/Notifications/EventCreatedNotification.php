<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi email ke church_admin saat acara penting dibuat (Task A slot sore).
 *
 * - Dipakai oleh EventObserver::created().
 * - Tidak memakai queue (driver belum dikonfigurasi) — kirim sinkron.
 */
class EventCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Event $event) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->event;
        $start = $event->start_datetime?->format('d M Y H:i');
        $end = $event->end_datetime?->format('d M Y H:i');

        $message = (new MailMessage)
            ->subject("[Portal Gereja] Acara baru: {$event->title}")
            ->greeting('Yth. Admin Gereja,')
            ->line('Sebuah acara baru telah dibuat di sistem portal gereja:')
            ->line("**{$event->title}**")
            ->line('Silakan buka panel admin untuk melihat detail dan mengelola petugas acara.')
            ->salutation('Sistem Portal Gereja');

        if ($start !== null) {
            $message->line("Waktu mulai: {$start}");
        }

        if ($end !== null) {
            $message->line("Waktu selesai: {$end}");
        }

        if (filled($event->location)) {
            $message->line("Lokasi: {$event->location}");
        }

        return $message;
    }
}
