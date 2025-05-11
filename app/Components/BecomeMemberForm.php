<?php

namespace App\Components;

use App\Models\AssociationMember;
use App\Notifications\AssociationMemberRegistered;
use Exception;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use WireUi\Traits\Actions;

class BecomeMemberForm extends Component
{
    use Actions;

    // Name der Firma (optional)
    #[Validate('nullable')]
    #[Validate('string', message: 'Der Name der Firma muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Name der Firma darf nicht länger als 255 Zeichen sein.')]
    public ?string $company_name = null;

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

    // Adresse
    #[Validate('required', message: 'Wir benötigen deine Adresse.')]
    #[Validate('string', message: 'Die Adresse muss ein Text sein.')]
    #[Validate('max:255', message: 'Die Adresse darf nicht länger als 255 Zeichen sein.')]
    public ?string $address = null;

    // PLZ
    #[Validate('required', message: 'Wir benötigen deine Postleitzahl.')]
    #[Validate('integer', message: 'Die Postleitzahl muss eine Zahl sein.')]
    #[Validate('digits:4', message: 'Die Postleitzahl muss vier Ziffern haben.')]
    public ?int $zip_code = null;

    // Ort
    #[Validate('required', message: 'Wir benötigen deinen Wohnort.')]
    #[Validate('string', message: 'Der Wohnort muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Wohnort darf nicht länger als 255 Zeichen sein.')]
    public ?string $city = null;

    // E-Mail
    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    #[Validate('unique:association_members,email', message: 'Die E-Mail-Adresse ist bereits registriert.')]
    public ?string $email = null;

    // E-Mail bestätigen
    #[Validate('required', message: 'Wir benötigen die Bestätigung deiner E-Mail-Adresse.')]
    #[Validate('same:email', message: 'Die E-Mail-Adressen stimmen nicht überein.')]
    public ?string $email_confirmation = null;

    // Telefonnummer
    #[Validate('required', message: 'Wir benötigen deine Telefonnummer.')]
    #[Validate('string', message: 'Wir benötigen deine Telefonnummer.')]
    #[Validate('digits:10', message: 'Die Telefonnummer ist ungültig.')]
    public ?string $phone_number = null;

    // Kommentar
    #[Validate('nullable')]
    #[Validate('max:2000', message: 'Der Kommentar darf nicht länger als 2000 Zeichen sein.')]
    public ?string $comment = null;

    // statutes read checkbox
    #[Validate('accepted', message: 'Bitte lese die Statuten.')]
    public bool $statutes_read = false;

    public function render()
    {
        return view('forms.become-member-form');
    }

    public function submit()
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {

            if ($e->validator->errors()->count() > 1) {
                $title = 'Es sind '.$e->validator->errors()->count().' Fehler aufgetreten.';
                $description = implode('<br>', $e->validator->errors()->all());
            } else {
                $title = $e->validator->errors()->first();
                $description = 'Bitte überprüfe deine Angaben.';
            }

            $this->dialog([
                'title' => $title,
                'description' => $description,
                'icon' => 'error',
            ]);

            return;
        }

        try {

            $member = AssociationMember::create([
                'company_name' => $this->company_name,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'address' => $this->address,
                'zip_code' => $this->zip_code,
                'city' => $this->city,
                'email' => $this->email,
                'phone_number' => $this->phone_number,
                'comment' => $this->comment,
            ]);

            $this->reset();

            $member->notify(new AssociationMemberRegistered($member->first_name));

            $this->dialog([
                'title' => 'Danke!',
                'description' => 'Vielen Dank für deine Anmeldung. Wir melden und demnächst bei dir.',
                'icon' => 'mail-open',
                'onClose' => [
                    'method' => 'redirectHelper',
                ],
            ]);

        } catch (Exception $e) {

            $this->dialog([
                'title' => 'Fehler',
                'description' => 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.',
                'icon' => 'error',
            ]);
        }
    }

    public function redirectHelper(): void
    {
        $this->redirect(route('home'), navigate: true);
    }
}
