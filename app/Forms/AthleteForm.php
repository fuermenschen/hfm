<?php

namespace App\Forms;

use App\Models\Athlete;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AthleteForm extends Form
{
    #[Validate("required", message: "Wir benötigen deinen Vornamen.")]
    #[Validate("string", message: "Ein Vorname aus anderem als Buchstaben? :)")]
    #[
        Validate(
            "max:255",
            message: "Dein Vorname ist zu lang für unsere Datenbank."
        )
    ]
    #[
        Validate(
            "min:2",
            message: "Dein Vorname kann so kurz nicht sein, oder? :)."
        )
    ]
    public string $first_name = "";

    #[Validate("required", message: "Wir benötigen deinen Nachnamen.")]
    #[
        Validate(
            "string",
            message: "Ein Nachname aus anderem als Buchstaben? :)"
        )
    ]
    #[
        Validate(
            "max:255",
            message: "Dein Nachname ist zu lang für unsere Datenbank."
        )
    ]
    #[
        Validate(
            "min:2",
            message: "Dein Nachname kann so kurz nicht sein, oder? :)."
        )
    ]
    public string $last_name = "";

    #[Validate("required", message: "Wir benötigen dein Geburtsdatum.")]
    #[Validate("date", message: "Bitte gib ein gültiges Datum ein.")]
    public string $date_of_birth = "";

    public function save(): void
    {
        $this->validate();

        Athlete::create($this->all());
    }
}
