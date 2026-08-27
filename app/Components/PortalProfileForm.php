<?php

declare(strict_types=1);

namespace App\Components;

use App\Models\ExternalUser;
use App\Rules\ValidZipCode;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Propaganistas\LaravelPhone\PhoneNumber;

class PortalProfileForm extends Component
{
    #[Validate('required', message: 'Wir benötigen deine Adresse.')]
    #[Validate('string', message: 'Die Adresse muss ein Text sein.')]
    #[Validate('max:255', message: 'Die Adresse darf nicht länger als 255 Zeichen sein.')]
    public string $address = '';

    #[Validate('required', message: 'Wir benötigen deine Postleitzahl.')]
    #[Validate('string', message: 'Die Postleitzahl muss ein Text sein.')]
    public string $zip_code = '';

    #[Validate('required', message: 'Wir benötigen deinen Wohnort.')]
    #[Validate('string', message: 'Der Wohnort muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Wohnort darf nicht länger als 255 Zeichen sein.')]
    public string $city = '';

    #[Validate('required', message: 'Wir benötigen deine Telefonnummer.')]
    #[Validate('phone', message: 'Die Telefonnummer muss mit internationaler Ländervorwahl angegeben werden.')]
    public string $phone_number = '';

    #[Locked]
    public string $country_of_residence = '';

    public function mount(): void
    {
        $externalUser = $this->externalUser();

        $this->address = $externalUser->address;
        $this->zip_code = $externalUser->zip_code;
        $this->city = $externalUser->city;
        $this->country_of_residence = $externalUser->country_of_residence;
        $this->phone_number = $externalUser->phone_number;
    }

    public function save(): void
    {
        $externalUser = $this->externalUser();
        $this->validate();
        Validator::validate(
            ['zip_code' => $this->zip_code],
            ['zip_code' => [new ValidZipCode($externalUser->country_of_residence)]],
        );

        $externalUser->fill([
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'phone_number' => new PhoneNumber($this->phone_number)->formatInternational(),
        ])->save();

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Deine Kontaktdaten wurden aktualisiert.',
            variant: 'success',
        );

        $this->redirectRoute('portal.dashboard', navigate: true);
    }

    public function render(): Factory|View
    {
        return view('components.portal-profile-form');
    }

    protected function externalUser(): ExternalUser
    {
        $externalUser = auth('external')->user();

        abort_unless($externalUser instanceof ExternalUser, 403);

        return $externalUser;
    }
}
