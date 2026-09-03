<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Notifikasi email terjadwal (Task slot sore — Notifikasi email + scheduler).
 *
 * Driver queue saat ini `sync` (belum ada worker), jadi notifikasi terkirim
 * sinkron saat command dijalankan scheduler. Bila queue worker dipasang nanti,
 * Notification memakai Queueable sehingga otomatis di-antrekan.
 */
Schedule::command('warta:notify-birthdays')->dailyAt('06:00');
Schedule::command('warta:notify-schedule')->dailyAt('05:30');
