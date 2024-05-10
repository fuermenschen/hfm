<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Partner;
use App\Models\SportType;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Lukeraymonddowning\Honey\Traits\WithHoney;
use WireUi\Traits\Actions;

class BecomeAthleteForm extends Component
{
    use Actions;
    use WithHoney;

    // Vorname
    #[Validate("required", message: "Wir benötigen deinen Vornamen.")]
    #[Validate("string", message: "Der Vorname muss ein Text sein.")]
    #[Validate("max:255", message: "Der Vorname darf nicht länger als 255 Zeichen sein.")]
    public ?string $first_name = null;

    // Nachname
    #[Validate("required", message: "Wir benötigen deinen Nachnamen.")]
    #[Validate("string", message: "Der Nachname muss ein Text sein.")]
    #[Validate("max:255", message: "Der Nachname darf nicht länger als 255 Zeichen sein.")]
    public ?string $last_name = null;

    // Adresse
    #[Validate("required", message: "Wir benötigen deine Adresse.")]
    #[Validate("string", message: "Die Adresse muss ein Text sein.")]
    #[Validate("max:255", message: "Die Adresse darf nicht länger als 255 Zeichen sein.")]
    public ?string $address = null;

    // PLZ
    #[Validate("required", message: "Wir benötigen deine Postleitzahl.")]
    #[Validate("integer", message: "Die Postleitzahl muss eine Zahl sein.")]
    #[Validate("digits:4", message: "Die Postleitzahl muss vier Ziffern haben.")]
    public ?int $zip_code = null;

    // Ort
    #[Validate("required", message: "Wir benötigen deinen Wohnort.")]
    #[Validate("string", message: "Der Wohnort muss ein Text sein.")]
    #[Validate("max:255", message: "Der Wohnort darf nicht länger als 255 Zeichen sein.")]
    public ?string $city = null;

    // E-Mail
    #[Validate("required", message: "Wir benötigen deine E-Mail-Adresse.")]
    #[Validate("email", message: "Bitte gib eine gültige E-Mail-Adresse ein.")]
    #[Validate("unique:athletes,email", message: "Die E-Mail-Adresse ist bereits registriert.")]
    public ?string $email = null;

    // E-Mail bestätigen
    #[Validate("required", message: "Wir benötigen die Bestätigung deiner E-Mail-Adresse.")]
    #[Validate("same:email", message: "Die E-Mail-Adressen stimmen nicht überein.")]
    public ?string $email_confirmation = null;

    // Telefonnummer
    #[Validate("required", message: "Wir benötigen deine Telefonnummer.")]
    #[Validate("string", message: "Wir benötigen deine Telefonnummer.")]
    #[Validate("size:10", message: "Die Telefonnummer besteht aus 10 Zahlen.")]
    public ?string $phone_number = null;

    // Volljährig?
    #[Validate("required", message: "Wir benötigen diese Information.")]
    public bool $adult = false;

    // Sportart
    public ?Collection $sport_types = null;

    #[Validate("required", message: "Wir benötigen deine Sportart.")]
    #[Validate("exists:sport_types,id", message: "Die Sportart existiert nicht.")]
    public int $sport_type_id = 0;

    // Anzahl Runden geschätzt
    #[Validate("required", message: "Wir benötigen die Anzahl der geschätzten Runden.")]
    #[Validate("integer", message: "Die Anzahl der geschätzten Runden muss eine Zahl sein.")]
    #[Validate("min:1", message: "Die Anzahl der geschätzten Runden muss mindestens 1 sein.")]
    public ?int $rounds_estimated = null;

    // Partner
    public ?Collection $partners = null;

    #[Validate("required", message: "Bitte wähle eine:n Partner:in.")]
    #[Validate("exists:partners,id", message: "Partner:in existiert nicht.")]
    public int $partner_id = 0;

    // Kommentar
    #[Validate("nullable")]
    #[Validate("max:2000", message: "Der Kommentar darf nicht länger als 2000 Zeichen sein.")]
    public ?string $comment = null;

    // privacy checkbox
    #[Validate("accepted", message: "Das muss akzeptiert werden.")]
    public bool $privacy = false;

    public function save(): void
    {
        try {
            if ($this->honeyPasses()) {
                $this->addError("Spam detected", "Spam detected.");
            }

            $this->validate();
        } catch (ValidationException $e) {

            if ($e->validator->messages()->count() > 1) {
                $title = "Es sind " . $e->validator->messages()->count() . " Fehler aufgetreten.";
                $description = implode('<br>', $e->validator->messages()->all());
            } else {
                $title = $e->validator->messages()->first();
                $description = "Bitte überprüfe deine Angaben.";
            }

            $this->dialog([
                "title" => $title,
                "description" => $description,
                "icon" => "error",
            ]);

            return;
        }


        try {

            Athlete::create($this->all());

            $this->reset();

            $this->dialog([
                "title" => "Prüfe deine E-Mails!",
                "description" => "Vielen Dank für deine Anmeldung. Wir haben dir eine E-Mail mit weiteren Informationen gesendet. Deine Anmeldung ist erst nach Bestätigung der E-Mail gültig.",
                "icon" => "mail-open",
                "onClose" => [
                    "method" => "redirectHelper",
                ],
            ]);

        } catch (Exception $e) {
            $this->dialog([
                "title" => "Fehler",
                "description" => "Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.",
                "icon" => "error",
            ]);
        }
    }

    public function showPrivacyInfo(): void
    {
        $this->dialog([
            "title" => "Datenschutz",
            "description" =>
                "Wir benutzen deine Daten nur für Zwecke, die für die Organisation zwingend sind. Nach dem Anlass werden deine Daten gelöscht. Es werden niemals Daten an Dritte weitergegeben. Mehr Informationen findest du in der Datenschutzerklärung.<br><br><a href='/datenschutz' target='_blank' class='underline'>Datenschutzerklärung</a>",
            "icon" => "info",
        ]);
    }

    public function mount(): void
    {
        $this->sport_types = SportType::all();

        $this->partners = Partner::all();
    }

    public function updating($propertyName): void
    {
        $this->resetValidation($propertyName);
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function render(): View
    {
        return view("forms.become-athlete-form");
    }

    public function redirectHelper(): void
    {
        $this->redirect(route("home"), navigate: true);
    }
}
