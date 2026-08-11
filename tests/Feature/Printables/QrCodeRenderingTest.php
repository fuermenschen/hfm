<?php

use App\Models\AthleteRegistration;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;

it('renders athlete welcome letter with embedded png qr code', function (): void {
    $partner = Partner::factory()->create(['name' => 'Brühlgut Stiftung']);
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
    $athlete->setRelation('externalUser', $athleteIdentity);
    $athlete->setRelation('donationEvent', $athlete->donationEvent);

    $html = view('printables.athlete_welcome_letter', [
        'registration' => $athlete,
        'athlete' => $athleteIdentity,
        'event' => $athlete->donationEvent,
        'associationName' => 'Verein für Menschen',
        'associationDomain' => 'fuer-menschen.ch',
        'associationCity' => 'Winterthur',
        'officialAddress' => ['Verein für Menschen', 'c/o Kai Frehner', 'Rössligasse 6', '8400 Winterthur'],
        'mailFromAddress' => 'info@fuer-menschen.ch',
        'partnerName' => 'Brühlgut Stiftung',
        'eventDate' => '12.09.2026',
        'eventStartTime' => '11:00',
        'eventEndTime' => '16:00',
        'logoData' => base64_encode(file_get_contents(resource_path('images/logo_light.svg'))),
        'qrCodeDataUri' => 'data:image/png;base64,'.base64_encode('png'),
    ])->render();

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
