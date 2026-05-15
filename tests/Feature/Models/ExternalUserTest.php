<?php

use App\Models\ExternalUser;

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
