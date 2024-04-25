<?php

namespace App\Components;

use App\Forms\AthleteForm;
use Illuminate\View\View;
use Livewire\Component;

class BecomeAthlete extends Component
{
    public AthleteForm $form;

    public function save()
    {
        $this->form->save();

        $this->redirect("/", navigate: true);
    }

    public function mount()
    {
        // $this->form->types_of_sport = TypeOfSport::all()->pluck("name", "id")->all();
    }

    public function render(): View
    {
        return view("pages.become-athlete")->extends("layouts.public");
    }
}
