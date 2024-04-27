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
]);
Route::view("ueber-das-projekt", "pages.about");
Route::view("sportlerin-werden", "pages.become-athlete");
Route::view("sponsorin-werden", "pages.become-sponsor");
Route::view("informationen-zum-anlass", "pages.event-information");

// Footer Menu
Route::view("faq", "pages.f-a-q");
Route::view("kontakt", "pages.contact");
Route::view("impressum", "pages.impressum");
Route::view("datenschutz", "pages.privacy");
