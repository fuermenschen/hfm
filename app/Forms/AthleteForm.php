<?php

namespace App\Forms;

use App\Models\Athlete;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AthleteForm extends Form
{
    // Vorname
    #[Validate("required", message: "Wir benötigen deinen Vornamen.")]
    #[Validate("string", message: "Der Vorname muss ein Text sein.")]
    #[Validate("max:255", message: "Der Vorname darf nicht länger als 255 Zeichen sein.")]
    public string $first_name = "";

    // Nachname
    #[Validate("required", message: "Wir benötigen deinen Nachnamen.")]
    #[Validate("string", message: "Der Nachname muss ein Text sein.")]
    #[Validate("max:255", message: "Der Nachname darf nicht länger als 255 Zeichen sein.")]
    public string $last_name = "";

    // Adresse
    #[Validate("required", message: "Wir benötigen deine Adresse.")]
    #[Validate("string", message: "Die Adresse muss ein Text sein.")]
    #[Validate("max:255", message: "Die Adresse darf nicht länger als 255 Zeichen sein.")]
    public string $address = "";

    // PLZ
    #[Validate("required", message: "Wir benötigen deine Postleitzahl.")]
    #[Validate("integer", message: "Die Postleitzahl muss eine Zahl sein.")]
    #[Validate("digits:4", message: "Die Postleitzahl muss vier Ziffern haben.")]
    public ?int $zip_code = null;

    // Ort
    #[Validate("required", message: "Wir benötigen deinen Wohnort.")]
    #[Validate("string", message: "Der Wohnort muss ein Text sein.")]
    #[Validate("max:255", message: "Der Wohnort darf nicht länger als 255 Zeichen sein.")]
    public string $city = "";

    // Telefonnummer
    #[Validate("required", message: "Wir benötigen deine Telefonnummer.")]
    #[Validate("string", message: "Wir benötigen deine Telefonnummer.")]
    #[Validate("max:13", message: "Die Telefonnummer darf nicht länger als 13 Zeichen sein.")]
    #[Validate("min:10", message: "Die Telefonnummer muss mindestens 10 Zeichen lang sein.")]
    public string $phone_number = "";

    // E-Mail
    #[Validate("required", message: "Wir benötigen deine E-Mail-Adresse.")]
    #[Validate("email", message: "Bitte gib eine gültige E-Mail-Adresse ein.")]
    public string $email = "";

    // Sportart
    // public $types_of_sport;
    #[Validate("required", message: "Wir benötigen deine Sportart.")]
    #[Validate("string", message: "Die Sportart muss ein Text sein.")]
    public string $type_of_sport;

    // Alter
    #[Validate("required", message: "Wir benötigen dein Alter.")]
    #[Validate("integer", message: "Das Alter muss eine Zahl sein.")]
    #[Validate("min:5", message: "Du musst mindestens 5 Jahre alt sein.")]
    public ?int $age = null;

    // Kommentar
    #[Validate("nullable", message: "Der Kommentar darf leer gelassen werden.")]
    #[Validate("string", message: "Der Kommentar muss ein Text sein.")]
    public string $comment = "";

    // Sponsoring Token
    public ?int $sponsoring_token = null;

    // privacy checkbox
    #[Validate("accepted", message: "Das muss akzeptiert werden.")]
    public bool $privacy = false;

    public function save(): void
    {
        $this->validate();

        $this->sponsoring_token = $this->generateSponsoringToken();

        Athlete::create($this->all());
    }

    private function generateSponsoringToken(): int
    {
        $token = mt_rand(100000000, 999999999);

        if ($this->tokenExists($token)) {
            return $this->generateSponsoringToken();
        }

        return $token;
    }

    private function tokenExists(int $token): bool
    {
        return Athlete::where("sponsoring_token", $token)->exists();
    }
}
