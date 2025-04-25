<?php

use App\Models\Athlete;
use App\Models\User;
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
    $athleteCount = Schema::hasTable('athletes') ? \App\Models\Athlete::count() : 0;
    $donationCount = Schema::hasTable('donations') ? \App\Models\Donation::count() : 0;

    return view('home', compact('athleteCount', 'donationCount'));
})->name('home');
Route::view('sportlerin-werden', 'pages.become-athlete')->name('become-athlete');
Route::view('spenderin-werden', 'pages.become-donator')->name('become-donator');
Route::view('fragen-und-antworten', 'pages.questions-and-answers')->name('questions-and-answers');

// Footer Menu
Route::view('login', 'pages.login')->name('login');
Route::view('kontakt', 'pages.contact')->name('contact');
Route::view('impressum', 'pages.impressum')->name('impressum');
Route::view('datenschutz', 'pages.privacy')->name('privacy');
Route::view('verein', 'pages.association')->name('association');

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

// Donator
Route::get('spenderinnen/{login_token}', function ($login_token) {
    return view('pages.show-donator', [
        'login_token' => $login_token,
    ]);
})->name('show-donator');

Route::get('spenderinnen/{login_token}/{donation_id}', function ($login_token, $donation_id) {
    return view('pages.show-donator', [
        'login_token' => $login_token,
        'donation_id' => $donation_id,
    ]);
})->name('verify-donation');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::view('admin', 'pages.admin.dashboard')->name('admin.dashboard');
    Route::view('admin/sportlerinnen', 'pages.admin.athletes')->name('admin.athletes.index');
    Route::view('admin/spenderinnen', 'pages.admin.donators')->name('admin.donators.index');
    Route::view('admin/spenden', 'pages.admin.donations')->name('admin.donations.index');

    Route::post('logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});

/*// Preview Email
Route::get('/preview-email', function () {
    $user = User::factory()->create();
    $notification = new App\Notifications\AthleteRegistered(
        first_name: "Max",
        public_id_string: "123456",
        login_token: "123456"
    );
    return $notification->toMail($user)->render();
});*/

/*// Preview Invoice
Route::get('/preview-invoice', function () {
    $donator = Donator::get()->first();
    $donations = $donator->donations()->with(['athlete', 'athlete.partner'])->get();

    return view("printables.donator_invoice", [
        'donator' => $donator,
        'donations' => $donations,
    ]);
}); */
