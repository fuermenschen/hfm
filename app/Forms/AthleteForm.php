<?php

namespace App\Forms;

use App\Models\Athlete;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AthleteForm extends Form
{
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

    // Sportart
    public ?Collection $sport_types = null;

    #[Validate("required", message: "Wir benötigen deine Sportart.")]
    #[Validate("exists:sport_types,id", message: "Die Sportart existiert nicht.")]
    public int $sport_type = 0;

    // Alter
    #[Validate("required", message: "Wir benötigen dein Alter.")]
    #[Validate("integer", message: "Das Alter muss eine Zahl sein.")]
    #[Validate("min:5", message: "Du musst mindestens 5 Jahre alt sein.")]
    public ?int $age;

    // Kommentar
    #[Validate("nullable")]
    #[Validate("max:2000", message: "Der Kommentar darf nicht länger als 2000 Zeichen sein.")]
    public ?string $comment = null;

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

        $this->reset([
            "first_name",
            "last_name",
            "address",
            "zip_code",
            "city",
            "phone_number",
            "email",
            "sport_type",
            "age",
            "comment",
            "privacy",
        ]);
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
