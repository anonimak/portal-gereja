<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use App\Notifications\BirthdayNotification;
use App\Notifications\WorshipScheduleNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Task slot sore (Notifikasi email + scheduler) — command terjadwal
 * `warta:notify-birthdays` & `warta:notify-schedule`.
 */
class ScheduledNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeChurchAdmin(Church $church): User
    {
        return User::factory()->create(['church_id' => $church->id, 'role' => 'church_admin']);
    }

    private function makeMember(Church $church, Carbon $birthDate): Member
    {
        $family = Family::factory()->create(['church_id' => $church->id]);

        return Member::factory()->create([
            'church_id' => $church->id,
            'family_id' => $family->id,
            'status' => 'aktif',
            'birth_date' => $birthDate,
        ]);
    }

    public function test_notify_birthdays_mengirim_email_untuk_anggota_berulang_tahun_hari_ini(): void
    {
        Notification::fake();

        $church = Church::factory()->create();
        $admin = $this->makeChurchAdmin($church);

        // Anggota berulang tahun hari ini → admin menerima notifikasi.
        $this->makeMember($church, now()->startOfDay());
        // Anggota ulang tahun lain → tidak ikut.
        $this->makeMember($church, now()->startOfDay()->subDays(10));

        $this->artisan('warta:notify-birthdays')->assertSuccessful();

        Notification::assertSentTo($admin, BirthdayNotification::class);
    }

    public function test_notify_birthdays_tanpa_anggota_ulang_tahun_tidak_mengirim(): void
    {
        Notification::fake();

        $church = Church::factory()->create();
        $this->makeChurchAdmin($church);
        $this->makeMember($church, now()->startOfDay()->subDays(3));

        $this->artisan('warta:notify-birthdays')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_notify_birthdays_terisolasi_per_gereja_dan_role(): void
    {
        Notification::fake();

        $churchA = Church::factory()->create();
        $adminA = $this->makeChurchAdmin($churchA);
        $churchB = Church::factory()->create();
        $adminB = $this->makeChurchAdmin($churchB);
        $finance = User::factory()->create(['church_id' => $churchA->id, 'role' => 'finance_admin']);

        $this->makeMember($churchA, now()->startOfDay());
        $this->makeMember($churchB, now()->startOfDay());

        $this->artisan('warta:notify-birthdays')->assertSuccessful();

        Notification::assertSentTo($adminA, BirthdayNotification::class);
        Notification::assertSentTo($adminB, BirthdayNotification::class);
        Notification::assertNotSentTo($finance, BirthdayNotification::class);
    }

    public function test_notify_schedule_mengirim_jadwal_ibadah_7_hari_ke_depan(): void
    {
        Notification::fake();

        $church = Church::factory()->create();
        $admin = $this->makeChurchAdmin($church);

        Event::factory()->create([
            'church_id' => $church->id,
            'start_datetime' => now()->addDays(2),
            'end_datetime' => now()->addDays(2)->addHours(2),
            'location' => 'Gedung Gereja',
        ]);
        // Event di luar 7 hari → tidak ikut.
        Event::factory()->create([
            'church_id' => $church->id,
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHours(1),
        ]);

        $this->artisan('warta:notify-schedule')->assertSuccessful();

        Notification::assertSentTo($admin, WorshipScheduleNotification::class);
    }

    public function test_notify_schedule_tanpa_event_tidak_mengirim(): void
    {
        Notification::fake();

        $church = Church::factory()->create();
        $this->makeChurchAdmin($church);

        $this->artisan('warta:notify-schedule')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
