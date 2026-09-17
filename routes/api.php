<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\MemberPortalApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function (): void {
    // Autentikasi API
    Route::post('/login', [MemberPortalApiController::class, 'login'])->name('api.portal.login');

    // Endpoints terproteksi Portal Jemaat
    Route::middleware('portal.auth')->group(function (): void {
        Route::post('/logout', [MemberPortalApiController::class, 'logout'])->name('api.portal.logout');
        Route::get('/me', [MemberPortalApiController::class, 'me'])->name('api.portal.me');

        // Profil & Data Diri (anti-IDOR)
        Route::get('/profile', [MemberPortalApiController::class, 'profile'])->name('api.portal.profile');
        Route::get('/members/{member}', [MemberPortalApiController::class, 'showMember'])->name('api.portal.members.show');

        // Jadwal Ibadah & Event Mendatang
        Route::get('/events', [MemberPortalApiController::class, 'events'])->name('api.portal.events');
        Route::get('/schedules', [MemberPortalApiController::class, 'events'])->name('api.portal.schedules');
        Route::get('/events/{event}', [MemberPortalApiController::class, 'showEvent'])->name('api.portal.events.show');
        Route::get('/schedules/{event}', [MemberPortalApiController::class, 'showEvent'])->name('api.portal.schedules.show');

        // Warta Jemaat
        Route::get('/warta', [MemberPortalApiController::class, 'warta'])->name('api.portal.warta');
        Route::get('/warta/{publication}', [MemberPortalApiController::class, 'showWarta'])->name('api.portal.warta.show');

        // Persembahan & Donasi
        Route::get('/offerings', [MemberPortalApiController::class, 'offerings'])->name('api.portal.offerings');
        Route::post('/offerings', [MemberPortalApiController::class, 'storeOffering'])->name('api.portal.offerings.store');
    });
});
