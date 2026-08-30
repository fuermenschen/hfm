<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\ExternalUser;
use App\Notifications\PreviousDonorAthleteRegistered;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class ConfirmAthleteRegistrationAction
{
    public function __invoke(AthleteRegistration $athleteRegistration, ExternalUser $externalUser): void
    {
        throw_if($athleteRegistration->external_user_id !== $externalUser->id, AuthorizationException::class, 'Diese Registrierung gehört nicht zu deinem Profil.');

        $wasConfirmed = AthleteRegistration::query()
            ->whereKey($athleteRegistration->id)
            ->where('verified', false)
            ->update(['verified' => true]) === 1;

        if (! $wasConfirmed) {
            return;
        }

        $athleteRegistration->refresh()->loadMissing('externalUser', 'donationEvent');

        if ($athleteRegistration->notify_previous_donors) {
            $this->previousDonors($athleteRegistration)
                ->each(function (ExternalUser $donor) use ($athleteRegistration): void {
                    $donor->notify(new PreviousDonorAthleteRegistered(
                        firstName: $donor->first_name,
                        athletePrivacyName: $athleteRegistration->externalUser->privacy_name,
                        donationEventTitle: $athleteRegistration->donationEvent->title,
                    ));
                });
        }
    }

    /**
     * @return Collection<int, ExternalUser>
     */
    protected function previousDonors(AthleteRegistration $athleteRegistration): Collection
    {
        return ExternalUser::query()
            ->whereKeyNot($athleteRegistration->external_user_id)
            ->whereHas('donationsAsDonor', function ($donations) use ($athleteRegistration): void {
                $donations->whereHas('athleteRegistration', function ($registrations) use ($athleteRegistration): void {
                    $registrations
                        ->where('external_user_id', $athleteRegistration->external_user_id)
                        ->where('donation_event_id', '!=', $athleteRegistration->donation_event_id);
                });
            })
            ->orderBy('id')
            ->get();
    }
}
