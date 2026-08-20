<?php

use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows verified event registrations to view groups and admins to manage them', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $viewer = eventGroupTestRegistration($event);
    $externalAbilities = ['viewPendingRequests', 'processRequests', 'removeMembers', 'manageAdmins', 'delete'];

    expect(Gate::forUser($viewer->externalUser)->allows('view', $group))->toBeTrue()
        ->and(Gate::forUser($admin->externalUser)->allows('view', $group))->toBeTrue();

    foreach ($externalAbilities as $ability) {
        expect(Gate::forUser($admin->externalUser)->allows($ability, $group))->toBeTrue()
            ->and(Gate::forUser($viewer->externalUser)->allows($ability, $group))->toBeFalse();
    }
});

it('denies users without a verified registration and web admins', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $registration = eventGroupTestRegistration($event);
    $registration->update(['verified' => false]);

    expect(Gate::forUser($registration->externalUser)->allows('view', $group))->toBeFalse()
        ->and(Gate::forUser(User::factory()->create())->allows('view', $group))->toBeFalse();
});

it('authorizes groups without loading their donation event', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $group->unsetRelation('donationEvent');

    expect($group->relationLoaded('donationEvent'))->toBeFalse()
        ->and(Gate::forUser($admin->externalUser)->allows('view', $group))->toBeTrue()
        ->and(Gate::forUser($admin->externalUser)->allows('processRequests', $group))->toBeTrue()
        ->and($group->relationLoaded('donationEvent'))->toBeFalse();
});

it('denies group management when registration belongs to another event', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $admin->update(['donation_event_id' => eventGroupTestEvent()->id]);

    expect(Gate::forUser($admin->externalUser)->allows('viewPendingRequests', $group))->toBeFalse()
        ->and(Gate::forUser($admin->externalUser)->allows('processRequests', $group))->toBeFalse()
        ->and(Gate::forUser($admin->externalUser)->allows('removeMembers', $group))->toBeFalse()
        ->and(Gate::forUser($admin->externalUser)->allows('manageAdmins', $group))->toBeFalse()
        ->and(Gate::forUser($admin->externalUser)->allows('delete', $group))->toBeFalse();
});
