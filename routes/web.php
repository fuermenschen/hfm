<?php

use App\Components\About;
use App\Components\BecomeAthlete;
use App\Components\BecomeSponsor;
use App\Components\Contact;
use App\Components\EventInformation;
use App\Components\FAQ;
use App\Components\Home;
use App\Components\Impressum;
use App\Components\Privacy;
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

Route::get("/", Home::class);
Route::get("ueber-das-projekt", About::class);
Route::get("sportlerin-werden", BecomeAthlete::class);
Route::get("sponsorin-werden", BecomeSponsor::class);
Route::get("informationen-zum-anlass", EventInformation::class);

Route::get("faq", FAQ::class);
Route::get("kontakt", Contact::class);
Route::get("impressum", Impressum::class);
Route::get("datenschutz", Privacy::class);
