<?php

namespace App\Components;

use Livewire\Component;

class BecomeMemberForm extends Component
{
    public string $test = '';

    public function render()
    {
        return view('forms.become-member-form');
    }

    public function submit()
    {
        dump($this->test);
    }
}
