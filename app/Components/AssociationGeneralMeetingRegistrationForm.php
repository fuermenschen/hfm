<?php

namespace App\Components;

use App\Mail\GenericMailMessage;
use App\Models\AssociationMember;
use Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AssociationGeneralMeetingRegistrationForm extends Component
{
    // Vorname
    #[Validate('required', message: 'Wir benötigen deinen Vornamen.')]
    #[Validate('string', message: 'Der Vorname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Vorname darf nicht länger als 255 Zeichen sein.')]
    public ?string $first_name = null;

    // Nachname
    #[Validate('required', message: 'Wir benötigen deinen Nachnamen.')]
    #[Validate('string', message: 'Der Nachname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Nachname darf nicht länger als 255 Zeichen sein.')]
    public ?string $last_name = null;

    // E-Mail
    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    public ?string $email = null;

    public function render()
    {
        return view('components.association-general-meeting-registration-form');
    }

    public function submitRegistration()
    {
        $this->validate();

        $member = AssociationMember::query()
            ->where('first_name', $this->first_name)
            ->where('last_name', $this->last_name)
            ->where('email', $this->email)
            ->first();

        if (! $member) {
            Flux::toast(text: 'Wir konnten dich nicht finden. Bitte überprüfe deine Eingaben.', heading: 'Fehler', variant: 'danger');

            return;
        }

        try {

            $htmlBody = '<p>Liebe:r '.$member->first_name.'</p><p>Vielen Dank für deine Anmeldung zur Mitgliederversammlung.</p><p>Wir freuen uns, dich dort zu sehen!</p><p>Herzliche Grüsse<br>Der Vorstand</p>';

            Mail::to($member)->send(
                new GenericMailMessage(
                    subject: 'Du bist an der Mitgliederversammlung angemeldet',
                    html: $htmlBody,
                )
            );

            // Zurücksetzen der Formulardaten
            $this->reset();

            Flux::toast(text: 'Du hast dich erfolgreich zur Mitgliederversammlung angemeldet.', heading: 'Erfolg', variant: 'success');

        } catch (\Exception $e) {
            Flux::toast(text: 'Es gab ein Problem beim Senden der E-Mail. Bitte versuche es später erneut.', heading: 'Fehler', variant: 'danger');

            return;
        }
    }
}
