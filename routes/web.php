<?php

use App\Filament\Pages\LaporanRapatPage;
use App\Http\Controllers\WartaJemaatExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Nama route 'login' untuk middleware auth — redirect ke halaman login Filament.
Route::redirect('/login', '/admin/login')->name('login');

// Export routes — hanya user terautentikasi dengan role panel yang sah.
Route::post('/admin/laporan-rapat/export-excel', function () {
    $user = auth()->user();

    // Guard: hanya role panel yang diizinkan mengekspor laporan.
    abort_unless(in_array($user->role, ['super_admin', 'church_admin', 'finance_admin'], true), 403, 'Tidak diizinkan mengekspor laporan.');

    // Isi data periode langsung (tanpa lifecycle Livewire) — baca dari request.
    $page = app(LaporanRapatPage::class);
    $page->data = request()->only(['period_type', 'month', 'quarter', 'year']);

    return $page->exportToExcel();
})->middleware(['auth', 'verified'])->name('laporan-rapat.export-excel');

// Fase 3A — export Warta Jemaat (kontrak Pixel #14: POST + start_date/end_date).
Route::middleware(['auth', 'verified'])->prefix('admin/warta-jemaat')->group(function () {
    Route::post('/export-pdf', [WartaJemaatExportController::class, 'pdf'])->name('warta-jemaat.export-pdf');
    Route::post('/export-excel', [WartaJemaatExportController::class, 'excel'])->name('warta-jemaat.export-excel');
});
