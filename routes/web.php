<?php

use App\Filament\Pages\LaporanRapatPage;
use App\Http\Controllers\BaptisAnakExportController;
use App\Http\Controllers\BirthRecordExportController;
use App\Http\Controllers\DataMigrationController;
use App\Http\Controllers\DeathRecordExportController;
use App\Http\Controllers\MarriageExportController;
use App\Http\Controllers\MemberCsvController;
use App\Http\Controllers\PublicOfferingController;
use App\Http\Controllers\PublicWartaController;
use App\Http\Controllers\SidiExportController;
use App\Http\Controllers\WartaJemaatExportController;
use App\Http\Controllers\WartaPublishController;
use App\Models\Church;
use App\Models\LandingSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $churches = \Illuminate\Support\Facades\Schema::hasTable('churches') ? Church::all() : collect();
    $setting = \Illuminate\Support\Facades\Schema::hasTable('landing_settings') ? LandingSetting::current() : null;

    return view('welcome', compact('churches', 'setting'));
});

// Nama route 'login' untuk middleware auth — redirect ke halaman login Filament.
Route::redirect('/login', '/admin/login')->name('login');

// Persembahan & Donasi Digital Publik
Route::get('/persembahan/{churchCode?}', [PublicOfferingController::class, 'index'])->name('public.offering.index');
Route::post('/persembahan', [PublicOfferingController::class, 'store'])->name('public.offering.store');

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

// Publikasi Warta ke portal publik — admin publish snapshot periode.
Route::middleware(['auth', 'verified'])
    ->prefix('admin/warta')
    ->group(function () {
        Route::post('/publish', WartaPublishController::class)->name('warta.publish');
    });

// Portal publik Warta Jemaat — TANPA login; satu gereja per halaman (route by code).
Route::prefix('warta')->group(function () {
    Route::get('/{church?}', [PublicWartaController::class, 'index'])
        ->where('church', '[A-Za-z0-9\-]+')
        ->name('public.warta.index');
    Route::get('/{church}/{publication}', [PublicWartaController::class, 'show'])
        ->where('church', '[A-Za-z0-9\-]+')
        ->where('publication', '[0-9]+')
        ->name('public.warta.show');
});

// Import/Export CSV Jemaat - Task slot 07:00 Jumat 4 Sep.
Route::middleware(['auth', 'verified'])
    ->prefix('admin/csv-jemaat')
    ->group(function () {
        Route::get('/template', [MemberCsvController::class, 'template'])->name('csv-jemaat.template');
        Route::get('/export', [MemberCsvController::class, 'export'])->name('csv-jemaat.export');
        Route::post('/import', [MemberCsvController::class, 'import'])->name('csv-jemaat.import');
    });

// Rute Terproteksi Panel: Unduh Template & Eksekusi Import Excel (SPEC §6.1)
Route::middleware(['auth', 'verified'])->prefix('admin/migrasi-data')->group(function () {
    Route::get('/template/{module}', [DataMigrationController::class, 'downloadTemplate'])
        ->where('module', 'jemaat|keuangan|pelayan|acara')
        ->name('data-migration.template');

    Route::post('/import/{module}', [DataMigrationController::class, 'import'])
        ->where('module', 'jemaat|keuangan|pelayan|acara')
        ->name('data-migration.import');
});

// Task 4: Portal Mandiri Anggota (Web Interface)
Route::prefix('portal')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'showLoginForm'])->name('portal.login');
    Route::post('/login', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'login'])->name('portal.login.submit');
    Route::post('/logout', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'logout'])->name('portal.logout');

    Route::middleware('portal.auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'dashboard'])->name('portal.dashboard');
        Route::get('/profile', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'profile'])->name('portal.profile');
        Route::get('/events', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'events'])->name('portal.events');
        Route::get('/schedules', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'events'])->name('portal.schedules');
        Route::get('/events/{event}', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'showEvent'])->name('portal.events.show');
        Route::get('/warta', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'warta'])->name('portal.warta');
        Route::get('/warta/{publication}', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'showWarta'])->name('portal.warta.show');
        Route::get('/persembahan', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'offerings'])->name('portal.offerings');
        Route::post('/persembahan', [\App\Http\Controllers\Portal\MemberPortalWebController::class, 'storeOffering'])->name('portal.offerings.store');
    });
});
