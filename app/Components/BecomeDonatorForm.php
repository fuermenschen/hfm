<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donator;
use App\Models\Partner;
use Exception;
use Livewire\Attributes\Validate;
use Livewire\Component;
use WireUi\Traits\Actions;

class BecomeDonatorForm extends Component
{
    use Actions;

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

    // Telefonnummer
    #[Validate("required", message: "Wir benötigen deine Telefonnummer.")]
    #[Validate("string", message: "Wir benötigen deine Telefonnummer.")]
    #[Validate("size:10", message: "Die Telefonnummer besteht aus 10 Zahlen.")]
    public ?string $phone_number = null;

    // E-Mail
    #[Validate("required", message: "Wir benötigen deine E-Mail-Adresse.")]
    #[Validate("email", message: "Bitte gib eine gültige E-Mail-Adresse ein.")]
    public ?string $email = null;

    // E-Mail bestätigen
    #[Validate("required", message: "Wir benötigen die Bestätigung deiner E-Mail-Adresse.")]
    #[Validate("same:email", message: "Die E-Mail-Adressen stimmen nicht überein.")]
    public ?string $email_confirmation = null;

    // Athlet
    public ?array $athletes = null;
    public string $currentAthlete = "der:die Sportler:in";

    #[Validate("required", message: "Bitte wähle jemanden aus.")]
    #[Validate("min:0", message: "Sportler:in existiert nicht.")]
    #[Validate("exists:athletes,id", message: "Sportler:in existiert nicht.")]
    public ?int $athlete_id = 0;

    // Partners
    public ?array $partners = null;
    public string $currentPartner = "den:die Benefizpartner:in";

    // Summe pro Runde
    #[Validate("required", message: "Bitte gib einen Betrag ein.")]
    #[Validate("numeric", message: "Der Betrag muss eine Zahl sein.")]
    #[Validate("min:0.05", message: "Der Betrag muss mindestens Fr. 0.05 sein.")]
    public ?float $amount_per_round = null;

    // Maximalbetrag
    #[Validate("nullable")]
    #[Validate("numeric", message: "Der Betrag muss eine Zahl sein.")]
    #[Validate("min:1.00", message: "Der Betrag muss mindestens Fr. 1.- sein.")]
    #[Validate("gt:amount_min", message: "Der Betrag muss grösser als der Minimalbetrag sein.")]
    public ?float $amount_max = null;

    // Minimalbetrag
    #[Validate("nullable")]
    #[Validate("numeric", message: "Der Betrag muss eine Zahl sein.")]
    #[Validate("min:0.05", message: "Der Betrag muss mindestens Fr. 0.05 sein.")]
    #[Validate("gte:amount_per_round", message: "Der Betrag muss grösser oder gleich dem Betrag pro Runde sein.")]
    public ?float $amount_min = null;

    // Kommentar
    #[Validate("nullable")]
    #[Validate("max:2000", message: "Der Kommentar darf nicht länger als 2000 Zeichen sein.")]
    public ?string $comment = null;

    // privacy checkbox
    #[Validate("accepted", message: "Das muss akzeptiert werden.")]
    public bool $privacy = false;

    public function updateNames(): void
    {
        // if the athlete_id is set, get the athlete and partner name
        $athlete = Athlete::find($this->athlete_id);
        if ($athlete) {
            $this->currentAthlete = $athlete->privacy_name;
            $this->currentPartner = $athlete->partner->name;
        }
    }

    public function save(): void
    {
        $this->validate();

        try {

            // check if the donator already exists
            $donator = Donator::where("email", $this->email)->first();

            // if the donator does not exist, create a new one
            if (!$donator) {
                $donator = Donator::create($this->all());
            }

            // create a new donation
            $donator->donations()->create($this->all());

            $this->reset();

            $this->dialog([
                "title" => "Prüfe deine E-Mails",
                "description" => "Vielen Dank für deine Anmeldung zur Spende. Wir haben dir eine E-Mail mit weiteren Informationen gesendet. Deine Anmeldung ist erst nach Bestätigung der E-Mail gültig.",
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

    public function render()
    {
        return view("forms.become-donator-form");
    }

    public function showPrivacyInfo(): void
    {
        $this->dialog([
            "title" => "Datenschutz",
            "description" =>
                "Wir benutzen deine Daten nur für Zwecke, die für die Organisation zwingend sind. Nach dem Anlass werden deine Daten gelöscht. Es werden niemals Daten an Dritte weitergegeben. Mehr Informationen findest du in der Datenschutzerklärung.",
            "icon" => "info",
        ]);
    }

    public function showAmountInfo(): void
    {
        $athlete = $this->currentAthlete;
        $partner = $this->currentPartner;
        $message =
            "Der Betrag, den du pro Runde spenden möchtest, wird mit der Anzahl Runden multipliziert, die " .
            $athlete .
            " absolviert.<br><br>Falls " .
            $athlete .
            " sehr viele oder sehr wenige Runden absolviert, wird der Betrag auf das Minimum oder Maximum angepasst. Der Betrag wird nie unter das Minimum oder über das Maximum gehen.<br><br>Nach dem Anlass stellen wir dir eine Rechnung. Der Betrag geht dann zu <strong>100%</strong> an " .
            $partner .
            ".";

        $this->dialog([
            "title" => "Beiträge",
            "description" => $message,
            "icon" => "heart",
        ]);
    }

    public function mount()
    {
        // fetch all athletes
        $this->athletes = Athlete::all()
            ->where("verified", true)
            ->sortBy("first_name")
            ->select(["id", "privacy_name", "public_id_string", "partner_id"])
            ->toArray();

        // fetch all partners
        $this->partners = Partner::all()
            ->select(["id", "name"])
            ->toArray();
    }

    public function redirectHelper(): void
    {
        $this->redirect("/", navigate: true);
    }
}
