<?php

use App\Components\BecomeMemberForm;
use App\Models\AssociationMember;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(BecomeMemberForm::class)
        ->assertStatus(200);
});

it('can be filled with inputs', function () {
    Livewire::test(BecomeMemberForm::class)
        ->set('company_name', 'Test Company')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('email', 'john.doe@example.com')
        ->set('email_confirmation', 'john.doe@example.com')
        ->set('phone_number', '0791234567')
        ->set('comment', 'Looking forward to joining!')
        ->set('statutes_read', true)
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('association_members', [
        'company_name' => 'Test Company',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address' => '123 Test Street',
        'zip_code' => 1234,
        'city' => 'Test City',
        'email' => 'john.doe@example.com',
        'phone_number' => '0791234567',
        'comment' => 'Looking forward to joining!',
    ]);
});

it('cannot be submitted empty', function () {
    Livewire::test(BecomeMemberForm::class)
        ->set('first_name', '')
        ->call('submit')
        ->assertHasErrors();
});

it('cannot be submitted with existing email', function () {
    AssociationMember::factory()->create([
        'email' => 'jon@doe.com',
    ]);

    Livewire::test(BecomeMemberForm::class)
        ->set('company_name', 'Test Company')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('email', 'jon@doe.com')
        ->set('email_confirmation', 'jon@doe.com')
        ->set('phone_number', '0791234567')
        ->set('comment', 'Looking forward to joining!')
        ->set('statutes_read', true)
        ->call('submit')
        ->assertHasErrors([
            'email',
        ]);
});
