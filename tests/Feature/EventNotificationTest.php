<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Task A slot sore — notifikasi email ke church_admin saat event dibuat.
 */
class EventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_baru_mengirim_notifikasi_ke_church_admin(): void
    {
        Notification::fake();

        $church = Church::factory()->create();
        $admin = User::factory()->create(['church_id' => $church->id, 'role' => 'church_admin']);
        // User role lain tidak boleh menerima.
        User::factory()->create(['church_id' => $church->id, 'role' => 'finance_admin']);

        Event::factory()->create(['church_id' => $church->id]);

        Notification::assertSentTo($admin, EventCreatedNotification::class);
    }

    public function test_event_tanpa_church_admin_tidak_error(): void
    {
        Notification::fake();

        $church = Church::factory()->create();

        // Tidak ada user church_admin → observer harus no-op tanpa exception.
        Event::factory()->create(['church_id' => $church->id]);

        Notification::assertNothingSent();
    }
}
