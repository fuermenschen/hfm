<?php

namespace App\Components;

use App\Forms\AthleteForm;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\View\View;
use Livewire\Component;
use WireUi\Traits\Actions;

class BecomeAthlete extends Component
{
    use Actions;

    public AthleteForm $form;

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

    public function mount()
    {
        $this->form->sport_types = SportType::all();

        $this->form->partners = Partner::all();
    }

    public function render(): View
    {
        return view("pages.become-athlete")->extends("layouts.public");
    }

    public function redirectHelper(): void
    {
        $this->redirect("/", navigate: true);
    }
}
