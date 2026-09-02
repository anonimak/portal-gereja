<?php

use App\Filament\Pages\LaporanRapatPage;
use App\Http\Controllers\BaptisAnakExportController;
use App\Http\Controllers\BirthRecordExportController;
use App\Http\Controllers\DeathRecordExportController;
use App\Http\Controllers\MarriageExportController;
use App\Http\Controllers\SidiExportController;
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

// Fase 3B T9 — penerbitan Akta Nikah (dompdf).
Route::middleware(['auth', 'verified'])
    ->prefix('admin/marriage')
    ->group(function () {
        Route::get('/{marriage}/export-pdf', [MarriageExportController::class, 'pdf'])
            ->name('marriage.export-pdf');
    });

// Fase 3B T11 — penerbitan Surat Keterangan Kematian (dompdf).
Route::middleware(['auth', 'verified'])
    ->prefix('admin/death-record')
    ->group(function () {
        Route::get('/{deathRecord}/export-pdf', [DeathRecordExportController::class, 'pdf'])
            ->name('death-record.export-pdf');
    });

// Fase 3B T8 — penerbitan Dokumen Sidi / Dokumen Baptis Dewasa (dompdf).
Route::middleware(['auth', 'verified'])
    ->prefix('admin/sakramen/sidi')
    ->group(function () {
        Route::get('/{sacrament}/export-pdf', [SidiExportController::class, 'pdf'])
            ->name('sakramen.sidi.export-pdf');
    });
