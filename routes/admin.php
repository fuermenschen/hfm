<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonationEventSettingsController;
use App\Http\Controllers\Admin\DownloadAthleteStoryImageController;
use App\Http\Controllers\Admin\WeblingInterfaceTestPdfController;
use App\Http\Controllers\AdminSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:web')->group(function (): void {
    Route::get('admin', DashboardController::class)->name('admin.dashboard');
    Route::view('admin/anlaesse', 'pages.admin.donation-events')->name('admin.donation-events.index');
    Route::get('admin/anlaesse/neu', [DonationEventSettingsController::class, 'create'])->name('admin.donation-events.create');
    Route::get('admin/anlaesse/{donationEvent}/bearbeiten', [DonationEventSettingsController::class, 'edit'])->name('admin.donation-events.edit');
    Route::view('admin/partner', 'pages.admin.partners')->name('admin.partners.index');
    Route::view('admin/sponsoren', 'pages.admin.sponsors')->name('admin.sponsors.index');
    Route::view('admin/faqs', 'pages.admin.faqs')->name('admin.faqs.index');
    Route::view('admin/sportlerinnen', 'pages.admin.people', ['title' => 'Sportler:innen', 'role' => 'athlete'])->name('admin.athletes.index');
    Route::get('admin/sportlerinnen/{athleteRegistration}/story/{variant}', DownloadAthleteStoryImageController::class)
        ->whereIn('variant', ['light', 'dark'])
        ->name('admin.story-image.download');
    Route::view('admin/spenderinnen', 'pages.admin.people', ['title' => 'Spender:innen', 'role' => 'donor'])->name('admin.donors.index');
    Route::view('admin/spenden', 'pages.admin.donations')->name('admin.donations.index');
    Route::view('admin/dateien', 'pages.admin.files')->name('admin.files.index');
    Route::view('admin/tools', 'pages.admin.tools')->name('admin.tools');
    Route::get('admin/tools/webling-interface-test/pdf', WeblingInterfaceTestPdfController::class)
        ->middleware('signed')
        ->name('admin.tools.webling-interface-test.pdf');
    Route::view('admin/einstellungen', 'pages.admin.settings')->name('admin.settings');
    Route::post('admin/logout', [AdminSessionController::class, 'destroy'])->name('admin.logout');
});
