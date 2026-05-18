<?php

use App\Http\Controllers\ExternalUserSessionController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('portal/login/{uuid}', [ExternalUserSessionController::class, 'store'])
    ->middleware('signed')
    ->name('portal.login.uuid')
    ->whereUuid('uuid');

Route::middleware('auth:external')->group(function (): void {
    Route::get('portal', PortalController::class)->name('portal.dashboard');

    Route::post('portal/logout', [ExternalUserSessionController::class, 'destroy'])->name('portal.logout');
});
