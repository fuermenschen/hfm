<?php

use App\Models\Athlete;
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
Route::view("/", "home", [
    'athleteCount' => Athlete::count(),
])->name("home");
Route::view("ueber-das-projekt", "pages.about")->name("about");
Route::view("sportlerin-werden", "pages.become-athlete")->name("become-athlete");
Route::view("spenderin-werden", "pages.become-donator")->name("become-donator");
Route::view("informationen-zum-anlass", "pages.event-information")->name("event-information");

// Footer Menu
Route::view("faq", "pages.f-a-q")->name("faq");
Route::view("kontakt", "pages.contact")->name("contact");
Route::view("impressum", "pages.impressum")->name("impressum");
Route::view("datenschutz", "pages.privacy")->name("privacy");

// Athlete
Route::get("sportlerinnen/{login_token}", function ($login_token) {
    return view("pages.show-athlete", [
        'login_token' => $login_token,
    ]);
})->name("show-athlete");

// Donator
Route::get("spenderinnen/{login_token}", function ($login_token) {
    return view("pages.show-donator", [
        'login_token' => $login_token
    ]);
})->name("show-donator");

Route::get("spenderinnen/{login_token}/{donation_id}", function ($login_token, $donation_id) {
    return view("pages.show-donator", [
        'login_token' => $login_token,
        'donation_id' => $donation_id,
    ]);
})->name("verify-donation");
