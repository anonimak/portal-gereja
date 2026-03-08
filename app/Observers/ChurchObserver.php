<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Church;
use Database\Seeders\DefaultFinanceSeeder;

class ChurchObserver
{
    /**
     * Handle the Church "created" event.
     */
    public function created(Church $church): void
    {
        // Seed default funds and financial categories for the new church
        $seeder = new DefaultFinanceSeeder();
        $seeder->run($church->id);
    }
}
