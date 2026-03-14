<?php

use App\Models\Donation;
use App\Models\Donator;
use App\Models\Donor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('maps donor model to donors table', function () {
    expect((new Donor)->getTable())->toBe('donors');
});

it('keeps donator model as compatibility shim', function () {
    $donator = new Donator;

    expect($donator)
        ->toBeInstanceOf(Donator::class)
        ->toBeInstanceOf(Donor::class)
        ->and($donator->getTable())->toBe('donors');
});

it('uses donor has many relation on donor_id foreign key', function () {
    $donor = new Donor;
    $relation = $donor->donations();

    expect($relation)
        ->toBeInstanceOf(HasMany::class)
        ->and($relation->getForeignKeyName())->toBe('donor_id');
});

it('uses donor relation with donor_id foreign key', function () {
    $donation = new Donation;

    $donorRelation = $donation->donor();

    expect($donorRelation)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($donorRelation->getRelated())->toBeInstanceOf(Donor::class)
        ->and($donorRelation->getForeignKeyName())->toBe('donor_id');
});
