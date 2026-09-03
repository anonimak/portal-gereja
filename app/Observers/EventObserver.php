<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Event;
use App\Models\User;
use App\Notifications\EventCreatedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Observer Event — kirim notifikasi email ke church_admin gereja terkait
 * saat acara baru dibuat. Error notifikasi tidak boleh menggagalkan create
 * (di-try/catch & di-log).
 */
class EventObserver
{
    public function created(Event $event): void
    {
        try {
            $admins = User::query()
                ->where('church_id', $event->church_id)
                ->where('role', 'church_admin')
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, new EventCreatedNotification($event));
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi acara baru', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
