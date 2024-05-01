<?php

use App\Http\Controllers\AthleteController;
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
Route::get("sportlerinnen/{login_token}", [AthleteController::class, 'show'])->name("athlete.show");
