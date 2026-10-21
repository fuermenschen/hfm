<?php

use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

it('opens card help without navigating and keeps the whole card linked', function (int $width): void {
    Route::get('/_test/stat-card', fn (): string => Blade::render(<<<'BLADE'
        <!DOCTYPE html>
        <html lang="de">
        <head>
            @vite(['resources/css/app.css', 'resources/js/app.js'])
            @fluxAppearance
        </head>
        <body class="p-8">
            <dl class="max-w-sm">
                <x-admin.stat-card title="Test metric" value="42" route="admin.donors.index" tooltip="Metric explanation" />
            </dl>
            @fluxScripts
        </body>
        </html>
        BLADE));
    actingAs(User::factory()->create(), 'web');

    $page = visit('/_test/stat-card')->resize($width, 900);
    expect($page->script(<<<'JS'
        (() => {
            const value = document.querySelector('dd').getBoundingClientRect();
            return document.elementFromPoint(value.x + value.width / 2, value.y + value.height / 2).closest('a') !== null;
        })()
        JS))->toBeTrue();
    $page->click('button[aria-label="Hinweis zu Test metric"]')
        ->assertSee('Metric explanation')
        ->assertPathIs('/_test/stat-card')
        ->assertNoJavaScriptErrors();
    $page->click('button[aria-label="Hinweis zu Test metric"]')
        ->click('a[href="'.route('admin.donors.index').'"]')
        ->assertPathIs('/admin/spenderinnen');
})->with(['desktop' => 1280, 'mobile' => 390]);

it('explains dashboard invoice counts without following the donor-table link', function (): void {
    $event = DonationEvent::factory()->create();
    DonorEventInvoice::factory()->forEvent($event)->verifiedPaid()->create();
    actingAs(User::factory()->create(), 'web');

    visit('/admin?anlass='.$event->slug)
        ->assertSee('Spenden (berechnet)')
        ->click('button[aria-label="Hinweis zu Zahlung verifiziert"]')
        ->assertSee('Mehrere Zahlungen für dieselbe Rechnung zählen einmal.')
        ->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();
});
