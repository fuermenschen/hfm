<?php

use App\Components\AdminAssociationMemberTable;
use App\Models\AssociationMember;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(AdminAssociationMemberTable::class)
        ->assertStatus(200);
});

it('shows members in table', function () {
    $member = AssociationMember::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Test',
        'email' => 'anna@example.com',
    ]);

    Livewire::test(AdminAssociationMemberTable::class)
        ->assertStatus(200)
        ->assertSee($member->first_name)
        ->assertSee($member->last_name)
        ->assertSee($member->email);
});

it('dispatches batch message event', function () {

    $members = AssociationMember::factory()->count(2)->create();

    Livewire::test(AdminAssociationMemberTable::class)
        ->set('checkboxValues', $members->pluck('id')->toArray())
        ->dispatch('sendBatchMail')
        ->call('sendBatchMail')
        ->assertDispatchedTo(
            'admin-association-member-message',
            'openMemberMessageModal',
            member_ids: $members->pluck('id')->toArray(),
        );
});
