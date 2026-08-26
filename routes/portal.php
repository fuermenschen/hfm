<?php

use App\Http\Controllers\AthleteRegistrationConfirmationController;
use App\Http\Controllers\DonorRegistrationConfirmationController;
use App\Http\Controllers\DownloadAthleteStoryImageController;
use App\Http\Controllers\DownloadAthleteWelcomeLetterController;
use App\Http\Controllers\ExternalUserSessionController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PortalDonationsController;
use App\Http\Controllers\PortalEventGroupController;
use App\Http\Controllers\PortalEventGroupDiscoveryController;
use App\Http\Controllers\PortalParticipationsController;
use App\Http\Controllers\PortalProfileController;
use App\Http\Controllers\PreviewAthleteStoryImageController;
use Illuminate\Support\Facades\Route;

Route::get('portal/login/{uuid}', [ExternalUserSessionController::class, 'store'])
    ->middleware('signed')
    ->name('portal.login.uuid')
    ->whereUuid('uuid');

Route::get('portal/login/{uuid}/registrierung/bestaetigen', AthleteRegistrationConfirmationController::class)
    ->middleware('signed')
    ->name('portal.athlete-registration.confirm')
    ->whereUuid('uuid');

Route::get('portal/login/{uuid}/spende/{donation}/bestaetigen', DonorRegistrationConfirmationController::class)
    ->middleware('signed')
    ->name('portal.donation.confirm')
    ->whereUuid('uuid');

Route::middleware('auth:external')->group(function (): void {
    Route::get('portal', PortalController::class)->name('portal.dashboard');
    Route::get('portal/profil', [PortalProfileController::class, 'show'])->name('portal.profile');
    Route::get('portal/teilnahmen', PortalParticipationsController::class)->name('portal.participations');
    Route::get('portal/teilnahmen/{athleteRegistration}/gruppen', PortalEventGroupDiscoveryController::class)->name('portal.event-groups.discover');
    Route::get('portal/gruppen/{eventGroup}', [PortalEventGroupController::class, 'show'])->name('portal.event-groups.show');
    Route::get('portal/spenden', PortalDonationsController::class)->name('portal.donations');
    Route::get('portal/teilnahmen/{athleteRegistration}/willkommensbrief', DownloadAthleteWelcomeLetterController::class)
        ->name('portal.welcome-letter.download');
    Route::get('portal/teilnahmen/{athleteRegistration}/story/{variant}', DownloadAthleteStoryImageController::class)
        ->whereIn('variant', ['light', 'dark'])
        ->name('portal.story-image.download');
    Route::get('portal/teilnahmen/{athleteRegistration}/story/{variant}/preview', PreviewAthleteStoryImageController::class)
        ->whereIn('variant', ['light', 'dark'])
        ->name('portal.story-image.preview');

    Route::post('portal/logout', [ExternalUserSessionController::class, 'destroy'])->name('portal.logout');
});
