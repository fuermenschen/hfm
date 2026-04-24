<?php

use App\Http\Controllers\Admin\WeblingInterfaceTestPdfController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\User;
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
Route::get('/', function () {
    $athleteCount = Schema::hasTable('athletes') ? Athlete::count() : 0;
    $donationCount = Schema::hasTable('donations') ? Donation::count() : 0;

    return view('home', compact('athleteCount', 'donationCount'));
})->name('home');
Route::view('sportlerin-werden', 'pages.become-athlete')->middleware('active-event')->name('become-athlete');
Route::view('spenderin-werden', 'pages.become-donor')->middleware('active-event')->name('become-donor');
Route::view('newsletter', 'pages.newsletter')->name('newsletter');
Route::get('newsletter/abmelden/{email}', [NewsletterSubscriptionController::class, 'show'])
    ->name('newsletter.unsubscribe')
    ->middleware('signed');
Route::post('newsletter/abmelden/{email}', [NewsletterSubscriptionController::class, 'update'])
    ->name('newsletter.unsubscribe.perform')
    ->middleware('signed');
Route::view('fragen-und-antworten', 'pages.questions-and-answers')->name('questions-and-answers');

// Footer Menu
Route::view('login', 'pages.login')->name('login');
Route::view('kontakt', 'pages.contact')->name('contact');
Route::view('impressum', 'pages.impressum')->name('impressum');
Route::view('datenschutz', 'pages.privacy')->name('privacy');
Route::view('verein', 'pages.association')->name('association');

// Results
Route::view('resultate', 'pages.results')->name('results');

// User Login
Route::get('login/{uuid}', function ($uuid) {

    // Get user by UUID
    $user = User::where('uuid', $uuid)->firstOrFail();

    // Login user
    auth()->login($user, true);

    // new session
    request()->session()->regenerate();

    // redirect to dashboard
    return redirect()->route('admin.dashboard');

})->name('login-uuid')->middleware('signed');

// Athlete
Route::get('sportlerinnen/{login_token}', function ($login_token) {
    return view('pages.show-athlete', [
        'login_token' => $login_token,
    ]);
})->name('show-athlete');

// Donor
Route::get('spenderinnen/{login_token}', function ($login_token) {
    return view('pages.show-donor', [
        'login_token' => $login_token,
    ]);
})->name('show-donor');

Route::get('spenderinnen/{login_token}/{donation_id}', function ($login_token, $donation_id) {
    return view('pages.show-donor', [
        'login_token' => $login_token,
        'donation_id' => $donation_id,
    ]);
})->name('verify-donation');

// Authenticated Routes
Route::middleware('auth')->group(function () {
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
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});

Route::get('queue-worker', function () {

    Artisan::call('queue:work --stop-when-empty --tries=3 --max-time=20');

    return 'Queue worker ran:<br><br>'.nl2br(Artisan::output());
})->middleware('api-key')->name('queue-worker');
