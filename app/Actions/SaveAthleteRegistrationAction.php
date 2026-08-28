<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;

class SaveAthleteRegistrationAction
{
    public function __construct(private ConfirmAthleteRegistrationAction $confirmAthleteRegistration) {}

    /**
     * @param  array{adult:bool, rounds_estimated:int, rounds_done:int, comment:?string, notify_previous_donors:bool, verified:bool}  $data
     */
    public function __invoke(AthleteRegistration $athleteRegistration, array $data): AthleteRegistration
    {
        $shouldConfirm = $data['verified'] && ! $athleteRegistration->verified;

        $athleteRegistration->fill([
            'adult' => $data['adult'],
            'rounds_estimated' => $data['rounds_estimated'],
            'rounds_done' => $data['rounds_done'],
            'comment' => filled($data['comment']) ? trim((string) $data['comment']) : null,
            'notify_previous_donors' => $data['notify_previous_donors'],
            'verified' => $shouldConfirm ? false : $data['verified'],
        ])->save();

        if ($shouldConfirm) {
            ($this->confirmAthleteRegistration)($athleteRegistration, $athleteRegistration->externalUser);
        }

        return $athleteRegistration;
    }
}
