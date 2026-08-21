<?php

use App\Actions\WithdrawEventGroupMembershipRequestAction;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use Illuminate\Validation\ValidationException;

it('clears every membership field when an applicant withdraws', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();

    resolve(WithdrawEventGroupMembershipRequestAction::class)($group, $applicant->externalUser);

    $applicant->refresh();

    expect($applicant->event_group_id)->toBeNull()
        ->and($applicant->group_membership_status)->toBeNull()
        ->and($applicant->group_membership_role)->toBeNull();
});

it('blocks withdrawal after event end', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();

    expect(fn () => resolve(WithdrawEventGroupMembershipRequestAction::class)($group, $applicant->externalUser))
        ->toThrow(ValidationException::class);
});
