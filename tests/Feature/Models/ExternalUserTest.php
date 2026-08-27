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

it('hides external user PII from serialization', function (): void {
    $user = ExternalUser::factory()->create([
        'email' => 'private@example.test',
        'address' => 'Secret Street 1',
        'zip_code' => '8000',
        'city' => 'Secret City',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 000 00 00',
    ]);

    $user->setAttribute('remember_token', 'secret-token');

    $hiddenKeys = [
        'remember_token',
        'email',
        'address',
        'zip_code',
        'city',
        'country_of_residence',
        'phone_number',
        'full_name',
    ];
    $json = json_decode($user->toJson(), true, 512, JSON_THROW_ON_ERROR);

    expect($user->toArray())->not->toHaveKeys($hiddenKeys)
        ->and($json)->not->toHaveKeys($hiddenKeys);
});
