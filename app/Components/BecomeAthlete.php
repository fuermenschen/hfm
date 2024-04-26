<?php

namespace App\Components;

use App\Forms\AthleteForm;
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

    public function mount()
    {
        $this->form->sport_types = SportType::all();
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
