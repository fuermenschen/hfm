<?php

namespace App\Components;

use App\Models\Athlete;
use App\Models\Donator;
use App\Models\Partner;
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

    // Athlet
    public ?array $athletes = null;
    public string $currentAthlete = "";

    #[Validate("required", message: "Bitte wähle jemanden aus.")]
    #[Validate("min:0", message: "Sportler:in existiert nicht.")]
    #[Validate("exists:athletes,id", message: "Sportler:in existiert nicht.")]
    public ?int $athlete_id = 0;

    // Partners
    public $partners = null;
    public string $currentPartner = "";

    // Summe pro Runde
    #[Validate("required", message: "Bitte gib einen Betrag ein.")]
    #[Validate("numeric", message: "Der Betrag muss eine Zahl sein.")]
    #[Validate("min:0.05", message: "Der Betrag muss mindestens Fr. 0.05 sein.")]
    public ?float $amount_per_round = null;

    // Maximalbetrag
    #[Validate("nullable")]
    #[Validate("numeric", message: "Der Betrag muss eine Zahl sein.")]
    #[Validate("min:1.00", message: "Der Betrag muss mindestens Fr. 1.- sein.")]
    public ?float $amount_max = null;

    // Minimalbetrag
    #[Validate("nullable")]
    #[Validate("numeric", message: "Der Betrag muss eine Zahl sein.")]
    #[Validate("min:0.05", message: "Der Betrag muss mindestens Fr. 0.05 sein.")]
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
        // if the athlete_id is set, get the athlete name
        if ($this->athlete_id) {
            $this->currentAthlete =
                $this->athletes[$this->athlete_id - 1]["first_name"] .
                " " .
                $this->athletes[$this->athlete_id - 1]["last_name"];
        }

        // if the athlete_id is set, get the partner of the athlete
        if ($this->athlete_id) {
            $this->currentPartner = $this->partners[$this->athletes[$this->athlete_id - 1]["partner_id"] - 1]["name"];
        }
    }

    public function save(): void
    {
        $this->validate();

        Donator::create($this->all());

        $this->reset([
            "first_name",
            "last_name",
            "address",
            "zip_code",
            "city",
            "phone_number",
            "email",
            "athlete_id",
            "amount_per_round",
            "amount_max",
            "amount_min",
            "comment",
            "privacy",
        ]);

        $this->dialog([
            "title" => "Erfolgreich registriert",
            "description" => "Vielen Dank für deine Anmeldung. Wir melden uns bald bei dir.",
            "icon" => "success",
            "onClose" => [
                "method" => "redirectHelper",
            ],
        ]);
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

        if ($athlete == "") {
            $athlete = "der:die Sportler:in";
        }
        if ($partner == "") {
            $partner = "die:der Benefizpartner:in";
        }

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
            ->sortBy("first_name")
            ->select(["id", "first_name", "last_name", "donation_token", "partner_id"])
            ->toArray();

        // change all last names to the first letter
        foreach ($this->athletes as $key => $athlete) {
            $this->athletes[$key]["last_name"] = substr($athlete["last_name"], 0, 1) . ".";
        }

        // change the donation token to a string: ######### --> "###-###-###"
        foreach ($this->athletes as $key => $athlete) {
            $this->athletes[$key]["donation_token"] =
                substr($athlete["donation_token"], 0, 3) .
                "-" .
                substr($athlete["donation_token"], 3, 3) .
                "-" .
                substr($athlete["donation_token"], 6, 3);
        }

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
