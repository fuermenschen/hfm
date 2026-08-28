<?php

use App\Components\AdminDonationEditor;
use App\Components\AdminDonationTable;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('edits donations after confirming changed fields while preserving donor and athlete relationships', function (): void {
    $logSpy = Log::spy();

    $donor = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create();
    $donation = Donation::factory()->forPair($donor, $registration)->create([
        'amount_per_round' => 2.00,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => false,
    ]);

    Livewire::test(AdminDonationEditor::class)
        ->call('open', $donation->id)
        ->set('amountPerRound', 5.00)
        ->set('amountMin', 10.00)
        ->set('amountMax', 100.00)
        ->set('comment', 'Aktualisierter Kommentar')
        ->set('verified', true)
        ->call('save')
        ->assertSet('confirmingSave', true)
        ->assertHasNoErrors()
        ->call('confirmSave')
        ->assertSet('modalOpen', false)
        ->assertHasNoErrors();

    $donation->refresh();

    expect($donation->amount_per_round)->toBe(5.00)
        ->and($donation->amount_min)->toBe(10.00)
        ->and($donation->amount_max)->toBe(100.00)
        ->and($donation->verified)->toBeTrue()
        ->and($donation->donor_external_user_id)->toBe($donor->id)
        ->and($donation->athlete_registration_id)->toBe($registration->id);

    $logSpy->shouldHaveReceived('info')
        ->with('Admin editor save confirmed.', [
            'editor' => 'AdminDonationEditor',
            'fields' => ['amountPerRound', 'amountMin', 'amountMax', 'comment', 'verified'],
            'admin' => auth()->user()->name,
            'donation_id' => $donation->id,
        ])
        ->once();
});

it('validates donation amount limits', function (): void {
    $donation = Donation::factory()->create();

    Livewire::test(AdminDonationEditor::class)
        ->call('open', $donation->id)
        ->set('amountPerRound', 5.00)
        ->set('amountMin', 4.00)
        ->set('amountMax', 3.00)
        ->call('save')
        ->assertHasErrors([
            'amountMin' => 'gte',
            'amountMax' => 'gte',
        ]);
});

it('renders donation editor actions in donation table', function (): void {
    Donation::factory()->create();

    Livewire::test(AdminDonationTable::class)
        ->set('eventSlug', '')
        ->assertSee('open-donation-editor', false);
});

it('rejects unauthenticated donation editor mutations', function (): void {
    auth('web')->logout();

    Livewire::test(AdminDonationEditor::class)
        ->call('open', 1)
        ->assertForbidden();
});
