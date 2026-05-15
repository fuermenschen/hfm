<?php

use App\Http\Controllers\ExternalUserSessionController;
use Illuminate\Support\Facades\Route;

Route::get('portal/login/{uuid}', [ExternalUserSessionController::class, 'store'])
    ->middleware('signed')
    ->name('portal.login.uuid')
    ->whereUuid('uuid');

Route::middleware('auth:external')->group(function (): void {
    Route::view('portal', 'pages.portal')->name('portal.dashboard');

    Route::post('portal/logout', [ExternalUserSessionController::class, 'destroy'])->name('portal.logout');
});
