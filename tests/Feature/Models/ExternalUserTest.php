<?php

use App\Models\ExternalUser;
use Illuminate\Support\Facades\Schema;

it('requires external user contact fields', function (): void {
    $columns = collect(Schema::getColumns('external_users'))->keyBy('name');

    expect($columns['address']['nullable'])->toBeFalse()
        ->and($columns['zip_code']['nullable'])->toBeFalse()
        ->and($columns['city']['nullable'])->toBeFalse()
        ->and($columns['phone_number']['nullable'])->toBeFalse();
});

it('generates a unique public id on external user create', function () {
    $user = ExternalUser::factory()->create();

    expect($user->public_id)
        ->toBeString()
        ->toHaveLength(6)
        ->toMatch('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/');
});

it('formats public id string as xxx-xxx', function () {
    $user = ExternalUser::factory()->create();

    expect($user->public_id_string)
        ->toMatch('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{3}-[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{3}$/');
});
