<?php

use App\Models\Athlete;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Vite as FoundationVite;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;

it('renders athlete welcome letter with embedded png qr code', function (): void {
    Vite::swap(new class extends FoundationVite
    {
        public function __invoke($entrypoints, $buildDirectory = null)
        {
            return new HtmlString('');
        }

        public function asset($asset, $buildDirectory = null)
        {
            return resource_path('images/letterhead_hfm.svg');
        }
    });

    $partner = Partner::create(['name' => 'Brühlgut Stiftung']);
    $sportType = SportType::create(['name' => 'Laufen']);

    $athlete = Athlete::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
        'login_token' => 'welcome-token-123',
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
    ]);

    $html = view('printables.athlete_welcome_letter', ['athlete' => $athlete])->render();

    expect($html)->toContain('data:image/png;base64,');
});

it('renders association donation invoice with embedded png qr bill image', function (): void {
    $html = view('printables.association-donation-invoice', [
        'first_name' => 'Anna',
        'last_name' => 'Muster',
        'address' => 'Musterstrasse 1',
        'zip_code' => '8400',
        'city' => 'Winterthur',
        'company_name' => 'Beispiel AG',
        'amount' => 120.50,
    ])->render();

    expect($html)
        ->toContain('id="qr-bill-swiss-qr-image"')
        ->toContain('data:image/png;base64,');
});
