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

it('normalizes invalid donor sorting input using shared allowlist validation', function (): void {
    $later = Donator::factory()->create(['first_name' => 'Zoe']);
    $earlier = Donator::factory()->create(['first_name' => 'Anna']);

    $component = Livewire::test(AdminDonatorTable::class)
        ->set('sortField', 'unsupported_sort_column')
        ->set('sortDirection', 'invalid-direction');

    $pageIds = $component->viewData('pageIds');
    $donorIds = $component->viewData('donors')->getCollection()->pluck('id')->all();

    expect($component->get('sortField'))->toBe('first_name');
    expect($component->get('sortDirection'))->toBe('asc');
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

it('filters donor rows when search input changes', function (): void {
    Donator::factory()->create(['first_name' => 'Anna']);
    Donator::factory()->create(['first_name' => 'Zoey']);

    Livewire::test(AdminDonatorTable::class)
        ->set('search', 'Anna')
        ->assertSee('Anna');
});

it('hydrates donor table search and sorting state from query parameters', function (): void {
    Donator::factory()->create(['first_name' => 'Anna', 'last_name' => 'Zeta']);
    Donator::factory()->create(['first_name' => 'Zoey', 'last_name' => 'Alpha']);

    Livewire::withQueryParams([
        'search' => 'Anna',
        'sortField' => 'last_name',
        'sortDirection' => 'desc',
    ])
        ->test(AdminDonatorTable::class)
        ->assertSet('search', 'Anna')
        ->assertSet('sortField', 'last_name')
        ->assertSet('sortDirection', 'desc')
        ->assertSee('Anna');
});

it('hydrates donor table pagination state from query parameters', function (): void {
    foreach (range(1, 30) as $index) {
        Donator::factory()->create(['first_name' => 'Name'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)]);
    }

    Livewire::withQueryParams([
        'perPage' => '25',
        'page' => '2',
    ])
        ->test(AdminDonatorTable::class)
        ->assertSet('perPage', 25)
        ->tap(function ($component): void {
            $paginator = $component->viewData('donors');

            expect($paginator->currentPage())->toBe(2);
            expect($component->viewData('pageIds'))->toHaveCount(5);
        });
});

it('toggles donor sort direction when clicking the same sortable column', function (): void {
    $anna = Donator::factory()->create(['first_name' => 'Anna']);
    $zoey = Donator::factory()->create(['first_name' => 'Zoey']);

    Livewire::test(AdminDonatorTable::class)
        ->assertSet('sortField', 'first_name')
        ->assertSet('sortDirection', 'asc')
        ->call('sortByColumn', 'first_name')
        ->assertSet('sortDirection', 'desc')
        ->tap(fn ($component) => expect($component->viewData('pageIds'))->toBe([$zoey->id, $anna->id]))
        ->call('sortByColumn', 'first_name')
        ->assertSet('sortDirection', 'asc')
        ->tap(fn ($component) => expect($component->viewData('pageIds'))->toBe([$anna->id, $zoey->id]));
});

it('persists donor visible columns in session across component reloads', function (): void {
    Livewire::test(AdminDonatorTable::class)
        ->assertSet('visibleColumns', fn (array $columns): bool => in_array('email', $columns, true))
        ->call('toggleColumn', 'email')
        ->assertSet('visibleColumns', fn (array $columns): bool => ! in_array('email', $columns, true));

    Livewire::test(AdminDonatorTable::class)
        ->assertSet('visibleColumns', fn (array $columns): bool => ! in_array('email', $columns, true));
});

it('builds a schema-driven visible column map with metadata', function (): void {
    $component = Livewire::test(AdminDonatorTable::class);

    $visibleDefinitions = $component->instance()->visibleColumnDefinitions();
    $visibleColumns = $component->get('visibleColumns');

    expect(array_keys($visibleDefinitions))->toBe($visibleColumns);
    expect($visibleDefinitions['first_name']['label'])->toBe('Vorname');
    expect($visibleDefinitions['first_name']['sortable'])->toBeTrue();
    expect($visibleDefinitions['invoice_total']['align'])->toBe('right');
    expect($visibleDefinitions['email']['tooltip'])->toBeTrue();
    expect($visibleDefinitions['email']['truncate'])->toBe(52);
    expect($visibleDefinitions['don_id']['export_key'])->toBe('DON-ID');
});

it('updates selected row counter when page selection is toggled', function (): void {
    $first = Donator::factory()->create();
    $second = Donator::factory()->create();

    Livewire::test(AdminDonatorTable::class)
        ->assertSee('Ausgewählt: 0')
        ->call('toggleSelectPage', [$first->id, $second->id])
        ->assertSee('Ausgewählt: 2')
        ->call('toggleSelectPage', [$first->id, $second->id])
        ->assertSee('Ausgewählt: 0');
});

it('shows inline donor empty-state hints with a reset action during filtered searches', function (): void {
    Donator::factory()->create(['first_name' => 'Anna']);

    Livewire::test(AdminDonatorTable::class)
        ->set('search', 'NichtVorhanden')
        ->assertSee('Keine Treffer für')
        ->assertSee('Suche zurücksetzen');
});

it('renders consistent table loading and action loading labels', function (): void {
    $sportType = SportType::query()->create(['name' => 'Schwimmen']);
    $partner = Partner::query()->create(['name' => 'Test Partner']);

    Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    Livewire::test(AdminDonatorTable::class)
        ->assertSee('Tabelle wird aktualisiert...')
        ->assertSee('Auswahl wird entfernt...')
        ->assertSee('Prüfe Zahlungsstatus...');

    Livewire::test(AdminAthleteTable::class)
        ->assertSee('Tabelle wird aktualisiert...')
        ->assertSee('Speichert...');

    Livewire::test(AdminDonationTable::class)
        ->assertSee('Tabelle wird aktualisiert...');
});
