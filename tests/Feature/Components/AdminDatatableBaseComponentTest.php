<?php

use App\Components\AdminAthleteTable;
use App\Components\AdminDonationTable;
use App\Components\AdminDonatorTable;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use App\Models\SportType;
use Livewire\Livewire;

it('uses shared fallback sorting and page id extraction for donor table', function (): void {
    $later = Donator::factory()->create(['first_name' => 'Zoe']);
    $earlier = Donator::factory()->create(['first_name' => 'Anna']);

    $component = Livewire::test(AdminDonatorTable::class)
        ->set('sortField', 'unsupported_sort_column');

    $pageIds = $component->viewData('pageIds');
    $donorIds = $component->viewData('donors')->getCollection()->pluck('id')->all();

    expect($pageIds)->toBe([$earlier->id, $later->id]);
    expect($donorIds)->toBe([$earlier->id, $later->id]);
});

it('hydrates rounds done inputs via shared athlete table render pipeline', function (): void {
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $partner = Partner::query()->create(['name' => 'Test Partner']);

    $athlete = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'rounds_done' => 7,
    ]);

    $component = Livewire::test(AdminAthleteTable::class);

    expect($component->viewData('pageIds'))->toContain($athlete->id);
    expect($component->get('roundsDoneInputs.'.$athlete->id))->toBe(7);
});

it('uses shared donation table render conventions', function (): void {
    $sportType = SportType::query()->create(['name' => 'Velofahren']);
    $partner = Partner::query()->create(['name' => 'Sponsoring']);

    $athlete = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'verified' => true,
    ]);

    $donator = Donator::factory()->create();

    $donation = Donation::query()->create([
        'donator_id' => $donator->id,
        'athlete_id' => $athlete->id,
        'amount_per_round' => 12,
        'amount_min' => 10,
        'amount_max' => 120,
        'comment' => 'Datatable shared render test',
    ]);

    $component = Livewire::test(AdminDonationTable::class);

    expect($component->viewData('pageIds'))->toContain($donation->id);
    expect($component->viewData('donations')->getCollection()->pluck('id')->all())->toContain($donation->id);
});

it('always renders clear selection button across admin datatables', function (): void {
    Livewire::test(AdminDonatorTable::class)
        ->assertSee('Auswahl entfernen')
        ->assertSee('Ausgewählt: 0');

    Livewire::test(AdminAthleteTable::class)
        ->assertSee('Auswahl entfernen')
        ->assertSee('Ausgewählt: 0');

    Livewire::test(AdminDonationTable::class)
        ->assertSee('Auswahl entfernen')
        ->assertSee('Ausgewählt: 0');
});

it('shows create invoice row action when no invoice exists yet', function (): void {
    Donator::factory()->create(['webling_data' => []]);

    Livewire::test(AdminDonatorTable::class)
        ->assertSee('Rechnung erstellen');
});

it('keeps donor bulk action labels stable without embedded selection counters', function (): void {
    Livewire::test(AdminDonatorTable::class)
        ->assertSee('Rechnungen erstellen')
        ->assertSee('Rechnungen herunterladen')
        ->assertSee('Rechnungen senden')
        ->assertSee('Erinnerungen senden')
        ->assertDontSee('Rechnungen erstellen (')
        ->assertDontSee('Rechnungen herunterladen (')
        ->assertDontSee('Rechnungen senden (')
        ->assertDontSee('Erinnerungen senden (');
});
