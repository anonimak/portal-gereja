<?php

use App\Filament\Pages\LaporanRapatPage;
use Illuminate\Support\Facades\Route;

// Liveness/healthcheck — dipakai healthcheck container nginx (GET /up)
Route::get('/up', fn () => response('ok', 200));

Route::get('/', function () {
    return view('welcome');
});

// Export routes — hanya user terautentikasi yang boleh mengakses halaman Laporan Rapat
Route::post('/admin/laporan-rapat/export-excel', function () {
    $page = app(LaporanRapatPage::class);

    // Guard: hanya user dengan akses ke halaman (role valid + ter-autentikasi)
    if (! $page::canView()) {
        abort(403, 'Tidak diizinkan mengekspor laporan.');
    }

    // Isi data periode langsung (tanpa lifecycle Livewire) — baca dari request
    $page->data = request()->only(['period_type', 'month', 'quarter', 'year']);

    return $page->exportToExcel();
})->middleware(['auth', 'verified'])->name('laporan-rapat.export-excel');
