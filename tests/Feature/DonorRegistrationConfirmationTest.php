<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('logs in external user from signed confirmation link without confirming donation', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $donation = Donation::factory()->forDonorExternalUser($externalUser)->create(['verified' => false]);

    get(donorConfirmationUrlForTest($externalUser, $donation))
        ->assertRedirect(route('portal.dashboard'));

    assertAuthenticatedAs($externalUser, 'external');
    expect($donation->refresh()->verified)->toBeFalse();
});

it('logs out admin session from signed confirmation link', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $donation = Donation::factory()->forDonorExternalUser($externalUser)->create(['verified' => false]);

    actingAs(User::factory()->create(), 'web');

    get(donorConfirmationUrlForTest($externalUser, $donation))
        ->assertRedirect(route('portal.dashboard'));

    assertGuest('web');
    assertAuthenticatedAs($externalUser, 'external');
});

it('confirms owned donation from authenticated portal action', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $donation = Donation::factory()->forDonorExternalUser($externalUser)->create(['verified' => false]);

    actingAs($externalUser, 'external');

    post(route('portal.donation.confirm.perform', $donation))
        ->assertRedirect(route('portal.donation.confirmed'));

    expect($donation->refresh()->verified)->toBeTrue();
});

it('keeps confirmation idempotent', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $donation = Donation::factory()->forDonorExternalUser($externalUser)->create(['verified' => true]);

    actingAs($externalUser, 'external');

    post(route('portal.donation.confirm.perform', $donation))
        ->assertRedirect(route('portal.donation.confirmed'));

    expect($donation->refresh()->verified)->toBeTrue();
});

it('rejects unsigned confirmation links', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $donation = Donation::factory()->forDonorExternalUser($externalUser)->create(['verified' => false]);

    get(route('portal.donation.confirm', [
        'uuid' => $externalUser->uuid,
        'donation' => $donation,
    ]))->assertForbidden();

    expect($donation->refresh()->verified)->toBeFalse();
});

it('rejects signed links for another external users donation', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $otherExternalUser = ExternalUser::factory()->create();
    $donation = Donation::factory()->forDonorExternalUser($otherExternalUser)->create(['verified' => false]);

    get(donorConfirmationUrlForTest($externalUser, $donation))->assertForbidden();

    expect($donation->refresh()->verified)->toBeFalse();
    assertGuest('external');
});

it('rejects portal confirmation for another external users donation', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $otherExternalUser = ExternalUser::factory()->create();
    $donation = Donation::factory()->forDonorExternalUser($otherExternalUser)->create(['verified' => false]);

    actingAs($externalUser, 'external');

    post(route('portal.donation.confirm.perform', $donation))->assertForbidden();

    expect($donation->refresh()->verified)->toBeFalse();
});

it('shows confirmation button for unverified donor donations in portal', function (): void {
    $externalUser = ExternalUser::factory()->create();
    $athlete = ExternalUser::factory()->create(['first_name' => 'Claudia', 'last_name' => 'Müller']);
    $registration = AthleteRegistration::factory()->forExternalUser($athlete)->create(['verified' => true]);
    $donation = Donation::factory()->forPair($externalUser, $registration)->create(['verified' => false]);

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSee('Claudia M.')
        ->assertSee('Spende bestätigen')
        ->assertSee(route('portal.donation.confirm.perform', $donation));
});

function donorConfirmationUrlForTest(ExternalUser $externalUser, Donation $donation): string
{
    return URL::temporarySignedRoute('portal.donation.confirm', now()->addMinutes(15), [
        'uuid' => $externalUser->uuid,
        'donation' => $donation,
    ]);
}
