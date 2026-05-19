<?php

use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterSubscriptionController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Main Menu
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('sportlerin-werden', 'pages.become-athlete')->middleware('active-event')->name('become-athlete');
Route::view('spenderin-werden', 'pages.become-donor')->middleware('active-event')->name('become-donor');
Route::view('newsletter', 'pages.newsletter')->name('newsletter');
Route::get('newsletter/abmelden/{email}', [NewsletterSubscriptionController::class, 'show'])
    ->name('newsletter.unsubscribe')
    ->middleware('signed');
Route::post('newsletter/abmelden/{email}', [NewsletterSubscriptionController::class, 'update'])
    ->name('newsletter.unsubscribe.perform')
    ->middleware('signed');
Route::get('fragen-und-antworten', [FaqController::class, 'index'])->name('questions-and-answers');

// Footer Menu
Route::view('login', 'pages.login')->name('login');
Route::view('kontakt', 'pages.contact')->name('contact');
Route::view('impressum', 'pages.impressum')->name('impressum');
Route::view('datenschutz', 'pages.privacy')->name('privacy');
Route::view('verein', 'pages.association')->name('association');

// Results
Route::view('resultate', 'pages.results')->name('results');

// User Login
Route::get('login/{uuid}', [AdminSessionController::class, 'store'])
    ->name('login-uuid')
    ->middleware('signed')
    ->whereUuid('uuid');

// Athlete
Route::get('sportlerinnen/{login_token}', function (): RedirectResponse {
    return to_route('portal.dashboard');
})->name('show-athlete');

// Donor
Route::get('spenderinnen/{login_token}', function (): RedirectResponse {
    return to_route('portal.dashboard');
})->name('show-donor');

Route::get('spenderinnen/{login_token}/{donation_id}', function (): RedirectResponse {
    return to_route('portal.dashboard');
})->name('verify-donation');

Route::get('queue-worker', function (): string {

    Artisan::call('queue:work --stop-when-empty --tries=3 --max-time=20');

    return 'Queue worker ran:<br><br>'.nl2br(Artisan::output());
})->middleware('api-key')->name('queue-worker');
