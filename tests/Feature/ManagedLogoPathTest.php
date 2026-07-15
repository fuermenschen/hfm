<?php

use App\Models\Partner;
use App\Models\Sponsor;
use Illuminate\Support\Facades\Storage;

it('always resolves partner logos inside the partners folder', function (): void {
    $partner = new Partner([
        'logo_light_filename' => 'nested/light.svg',
        'logo_dark_filename' => 'dark.svg',
    ]);

    expect($partner->logoLightUrl())->toBe(Storage::disk('public')->url('partners/nested/light.svg'))
        ->and($partner->logoDarkUrl())->toBe(Storage::disk('public')->url('partners/dark.svg'));
});

it('always resolves sponsor logos inside the sponsors folder', function (): void {
    $sponsor = new Sponsor([
        'logo_filename' => 'nested/logo.svg',
    ]);

    expect($sponsor->logoUrl())->toBe(Storage::disk('public')->url('sponsors/nested/logo.svg'));
});
