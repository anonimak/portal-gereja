<?php

use App\Filament\Pages\LaporanRapatPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Export routes
Route::post('/admin/laporan-rapat/export-excel', function () {
    $page = app(LaporanRapatPage::class);
    $page->form->fill(request()->only(['period_type', 'month', 'quarter', 'year']));
    return $page->exportToExcel();
})->middleware(['auth', 'verified'])->name('laporan-rapat.export-excel');
