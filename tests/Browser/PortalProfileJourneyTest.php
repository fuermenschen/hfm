<?php

use App\Models\ExternalUser;
use Livewire\Component;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('does not expose external user PII in the Livewire browser snapshot', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Visible',
        'last_name' => 'Person',
        'email' => 'livewire-private@example.test',
        'address' => 'Hidden Address 1',
        'zip_code' => '8000',
        'city' => 'Hidden City',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 000 00 00',
    ]);
    $externalUser->forceFill(['remember_token' => 'secret-token'])->save();

    $page = Livewire::visit(new class extends Component
    {
        public ExternalUser $externalUser;

        public string $firstName = '';

        public string $lastName = '';

        public function mount(int $externalUserId): void
        {
            $this->externalUser = ExternalUser::query()->findOrFail($externalUserId);
            $this->firstName = $this->externalUser->first_name;
            $this->lastName = $this->externalUser->last_name;
        }

        public function render(): string
        {
            return '<div data-public-id="'.$this->externalUser->public_id_string.'">'.$this->firstName.' '.$this->lastName.'</div>';
        }
    }, ['externalUserId' => $externalUser->id])
        ->assertNoJavaScriptErrors()
        ->assertSee($externalUser->first_name)
        ->assertSee($externalUser->last_name)
        ->assertAttribute('div[data-public-id]', 'data-public-id', $externalUser->public_id_string);

    /** @var array{data: array{externalUser: array{0: mixed, 1: array{class: string, key: int|string}}, firstName: string, lastName: string}} $snapshot */
    $snapshot = $page->script(<<<'JS'
        (() => {
            const element = document.querySelector('[wire\\:snapshot]');

            return JSON.parse(element.getAttribute('wire:snapshot'));
        })()
        JS);
    $snapshotJson = json_encode($snapshot, JSON_THROW_ON_ERROR);

    expect($snapshot['data']['firstName'])->toBe($externalUser->first_name)
        ->and($snapshot['data']['lastName'])->toBe($externalUser->last_name)
        ->and($snapshot['data']['externalUser'][1]['class'])->toBe(ExternalUser::class)
        ->and($snapshot['data']['externalUser'][1]['key'])->toEqual($externalUser->getKey());

    foreach (['remember_token', 'email', 'address', 'zip_code', 'city', 'country_of_residence', 'phone_number', 'full_name'] as $hiddenKey) {
        expect($snapshotJson)->not->toContain('"'.$hiddenKey.'"');
    }
});

it('lets an external user update their address but not their name', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Alte Adresse 1',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
    ]);

    actingAs($externalUser, 'external');

    $page = visit(route('portal.profile'));

    $page->assertNoJavaScriptErrors()
        ->assertPresent('input[readonly][value="Francesca"]')
        ->assertPresent('input[readonly][value="Arslan"]')
        ->type('[wire\:model\.live\.blur="address"]', 'Neue Adresse 2')
        ->pressAndWaitFor('Speichern', 0.2)
        ->assertPathIs('/portal')
        ->assertNoJavaScriptErrors();

    $externalUser->refresh();

    expect($externalUser->address)->toBe('Neue Adresse 2');

    $page->navigate(route('portal.profile'))
        ->click('Hilfe & Kontakt')
        ->assertPathIs('/kontakt')
        ->assertSee('Kontakt')
        ->assertNoJavaScriptErrors();
});
