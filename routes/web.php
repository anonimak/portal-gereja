<?php

use App\Http\Controllers\BaptisAnakExportController;
use App\Http\Controllers\BirthRecordExportController;
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

    $page = app(\App\Filament\Clusters\Reporting\Pages\LaporanRapatPage::class);

    $data = $page->getReportData();

    return $page->downloadExcel($data);
})->middleware(['auth', 'verified']);

// Fase 3B T5 — Akta Lahir (PDF via dompdf). GET + auth; guard role/church di controller.
Route::middleware(['auth', 'verified'])->prefix('admin/birth-record')->group(function () {
    Route::get('/{birthRecord}/export-pdf', [BirthRecordExportController::class, 'pdf'])->name('birth-record.export-pdf');
});

// Fase 3B T6 — penerbitan Dokumen Baptis Anak (dompdf).
Route::middleware(['auth', 'verified'])
    ->prefix('admin/sakramen/baptis-anak')
    ->group(function () {
        Route::get('/{sacrament}/export-pdf', [BaptisAnakExportController::class, 'pdf'])
            ->name('sakramen.baptis-anak.export-pdf');
    });
