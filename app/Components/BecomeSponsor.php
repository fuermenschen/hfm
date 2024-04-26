<?php

namespace App\Components;

use App\Forms\SponsorForm;
use App\Models\Athlete;
use App\Models\Partner;
use Livewire\Component;
use WireUi\Traits\Actions;

class BecomeSponsor extends Component
{
    use Actions;

    public SponsorForm $form;

    public function render()
    {
        return view("pages.become-sponsor")->extends("layouts.public");
    }

    public function updateNames(): void
    {
        $this->form->updateNames();
    }

    public function save()
    {
        $this->form->save();

        $this->dialog([
            "title" => "Erfolgreich registriert",
            "description" => "Vielen Dank für deine Anmeldung. Wir melden uns bald bei dir.",
            "icon" => "success",
            "onClose" => [
                "method" => "redirectHelper",
            ],
        ]);
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
        $athlete = $this->form->currentAthlete;
        $partner = $this->form->currentPartner;

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
        $this->form->athletes = Athlete::all()
            ->sortBy("first_name")
            ->select(["id", "first_name", "last_name", "sponsoring_token", "partner_id"])
            ->toArray();

        // change all last names to the first letter
        foreach ($this->form->athletes as $key => $athlete) {
            $this->form->athletes[$key]["last_name"] = substr($athlete["last_name"], 0, 1) . ".";
        }

        // change the sponsoring token to a string: ######### --> "###-###-###"
        foreach ($this->form->athletes as $key => $athlete) {
            $this->form->athletes[$key]["sponsoring_token"] =
                substr($athlete["sponsoring_token"], 0, 3) .
                "-" .
                substr($athlete["sponsoring_token"], 3, 3) .
                "-" .
                substr($athlete["sponsoring_token"], 6, 3);
        }

        // fetch all partners
        $this->form->partners = Partner::all()
            ->select(["id", "name"])
            ->toArray();
    }

    public function redirectHelper(): void
    {
        $this->redirect("/", navigate: true);
    }
}
