<?php

use App\Models\AthleteRegistration;
use App\Models\ExternalUser;
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

    $athleteIdentity = ExternalUser::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
    ]);

    $athlete = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteIdentity->id,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 8,
    ]);
    $athlete->forceFill([
        'first_name' => $athleteIdentity->first_name,
        'last_name' => $athleteIdentity->last_name,
        'address' => 'Musterstrasse 1',
        'zip_code' => '8400',
        'city' => 'Winterthur',
        'adult' => 1,
    ]);
    $athlete->setRelation('partner', $partner);
    $athlete->setRelation('sportType', $sportType);

    $html = view('printables.athlete_welcome_letter', ['athlete' => $athlete])->render();

    preg_match('/<img src="(data:image\/png;base64,[^"]+)" alt="QR Code"\s*\/>/', $html, $matches);

    expect($matches)
        ->toHaveKey(1)
        ->and(strlen($matches[1]))->toBeGreaterThan(strlen('data:image/png;base64,'));
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

    preg_match_all('/data:image\/png;base64,[A-Za-z0-9+\/=]+/', $html, $matches);

    expect($html)
        ->toContain('id="qr-bill-swiss-qr-image"')
        ->and($matches[0])
        ->not->toBeEmpty()
        ->and(strlen($matches[0][0]))->toBeGreaterThan(strlen('data:image/png;base64,'));

});
