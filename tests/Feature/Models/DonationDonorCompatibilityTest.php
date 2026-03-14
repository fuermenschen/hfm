<?php

use App\Models\Donation;
use App\Models\Donator;
use App\Models\Donor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('keeps donor model mapped to the legacy table', function () {
    expect((new Donor)->getTable())->toBe('donators');
});

it('keeps donator model as compatibility shim', function () {
    $donator = new Donator;

    expect($donator)
        ->toBeInstanceOf(Donator::class)
        ->toBeInstanceOf(Donor::class)
        ->and($donator->getTable())->toBe('donators');
});

it('keeps donor has many relation on legacy foreign key', function () {
    $donor = new Donor;
    $relation = $donor->donations();

    expect($relation)
        ->toBeInstanceOf(HasMany::class)
        ->and($relation->getForeignKeyName())->toBe('donator_id');
});

it('uses donor relation with legacy foreign key and keeps donator alias', function () {
    $donation = new Donation;

    $donorRelation = $donation->donor();
    $donatorRelation = $donation->donator();

    expect($donorRelation)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($donorRelation->getRelated())->toBeInstanceOf(Donor::class)
        ->and($donorRelation->getForeignKeyName())->toBe('donator_id')
        ->and($donatorRelation->getRelated())->toBeInstanceOf(Donor::class)
        ->and($donatorRelation->getForeignKeyName())->toBe('donator_id');
});
