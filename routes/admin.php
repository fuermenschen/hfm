<?php

use App\Http\Controllers\Admin\WeblingInterfaceTestPdfController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:web')->group(function (): void {
    Route::view('admin', 'pages.admin.dashboard')->name('admin.dashboard');
    Route::view('admin/anlaesse', 'pages.admin.donation-events')->name('admin.donation-events.index');
    Route::view('admin/partner', 'pages.admin.partners')->name('admin.partners.index');
    Route::view('admin/sponsoren', 'pages.admin.sponsors')->name('admin.sponsors.index');
    Route::view('admin/faqs', 'pages.admin.faqs')->name('admin.faqs.index');
    Route::view('admin/sportlerinnen', 'pages.admin.athletes')->name('admin.athletes.index');
    Route::view('admin/spenderinnen', 'pages.admin.donors')->name('admin.donors.index');
    Route::view('admin/spenden', 'pages.admin.donations')->name('admin.donations.index');
    Route::view('admin/tools', 'pages.admin.tools')->name('admin.tools');
    Route::get('admin/tools/webling-interface-test/pdf', WeblingInterfaceTestPdfController::class)
        ->middleware('signed')
        ->name('admin.tools.webling-interface-test.pdf');
    Route::view('admin/einstellungen', 'pages.admin.settings')->name('admin.settings');
    Route::post('logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('home');
    })->name('logout');
});
