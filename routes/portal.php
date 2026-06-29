<?php

use App\Http\Controllers\AthleteRegistrationConfirmationController;
use App\Http\Controllers\ExternalUserSessionController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('portal/login/{uuid}', [ExternalUserSessionController::class, 'store'])
    ->middleware('signed')
    ->name('portal.login.uuid')
    ->whereUuid('uuid');

Route::get('portal/login/{uuid}/registrierung/bestaetigen', AthleteRegistrationConfirmationController::class)
    ->middleware('signed')
    ->name('portal.athlete-registration.confirm')
    ->whereUuid('uuid');

Route::view('portal/registrierung/bestaetigt', 'pages.athlete-registration-confirmed')
    ->middleware('auth:external')
    ->name('portal.athlete-registration.confirmed');

Route::get('portal/login/{uuid}/spende/{donation}/bestaetigen', fn () => to_route('portal.dashboard'))
    ->middleware('signed')
    ->name('portal.donation.confirm')
    ->whereUuid('uuid');

Route::middleware('auth:external')->group(function (): void {
    Route::get('portal', PortalController::class)->name('portal.dashboard');

    Route::post('portal/registrierung/{athleteRegistration}/bestaetigen', [AthleteRegistrationConfirmationController::class, 'store'])
        ->name('portal.athlete-registration.confirm.perform');

    Route::post('portal/logout', [ExternalUserSessionController::class, 'destroy'])->name('portal.logout');
});
