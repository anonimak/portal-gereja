<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Church;
use Database\Seeders\DefaultFinanceSeeder;
use Database\Seeders\GuidanceTemplateSeeder;

class ChurchObserver
{
    /**
     * Handle the Church "created" event.
     */
    public function created(Church $church): void
    {
        // Seed default funds and financial categories for the new church
        $seeder = new DefaultFinanceSeeder;
        $seeder->run($church->id);

        // Seed default guidance templates (Pra-Sidi & Pra-Nikah, 12 sesi) — A13
        $templateSeeder = new GuidanceTemplateSeeder;
        $templateSeeder->run($church->id);
    }
}
