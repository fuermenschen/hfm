<?php

use App\Components\AdminExternalUserTable;
use App\Models\ExternalUser;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('renders AdminExternalUserTable', function (): void {
    Livewire::test(AdminExternalUserTable::class)
        ->assertSee('Ausgewählt: 0');
});

it('searches with query builder like clauses', function (): void {
    ExternalUser::factory()->create(['first_name' => 'Alpha']);
    ExternalUser::factory()->create(['first_name' => 'Control']);

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    Livewire::test(AdminExternalUserTable::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha')
        ->assertDontSee('Control');

    expect(collect($queries)->contains(
        fn (string $query): bool => str_contains($query, 'like ?'),
    ))->toBeTrue()
        ->and(collect($queries)->contains(
            fn (string $query): bool => str_contains($query, ' escape '),
        ))->toBeFalse();
});
