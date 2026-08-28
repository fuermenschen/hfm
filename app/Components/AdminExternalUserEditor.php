<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SaveExternalUserAction;
use App\Components\Concerns\ConfirmsAdminEdits;
use App\Models\ExternalUser;
use App\Rules\ValidZipCode;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class AdminExternalUserEditor extends Component
{
    use ConfirmsAdminEdits;

    #[Locked]
    public ?int $externalUserId = null;

    public bool $modalOpen = false;

    #[Validate('required', message: 'Bitte gib einen Vornamen ein.')]
    #[Validate('string', message: 'Der Vorname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Vorname darf nicht länger als 255 Zeichen sein.')]
    public string $firstName = '';

    #[Validate('required', message: 'Bitte gib einen Nachnamen ein.')]
    #[Validate('string', message: 'Der Nachname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Nachname darf nicht länger als 255 Zeichen sein.')]
    public string $lastName = '';

    #[Validate('required', message: 'Bitte gib eine Adresse ein.')]
    #[Validate('string', message: 'Die Adresse muss ein Text sein.')]
    #[Validate('max:255', message: 'Die Adresse darf nicht länger als 255 Zeichen sein.')]
    public string $address = '';

    #[Validate] // All rules are in rules() because postal validation depends on country.
    public string $zipCode = '';

    #[Validate('required', message: 'Bitte gib einen Ort ein.')]
    #[Validate('string', message: 'Der Ort muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Ort darf nicht länger als 255 Zeichen sein.')]
    public string $city = '';

    #[Validate('required', message: 'Bitte wähle ein Wohnsitzland aus.')]
    #[Validate('in:CH,DE,AT', message: 'Das Wohnsitzland ist ungültig.')]
    public string $countryOfResidence = 'CH';

    #[Validate('required', message: 'Bitte gib eine Telefonnummer ein.')]
    #[Validate('phone', message: 'Die Telefonnummer muss mit internationaler Ländervorwahl angegeben werden.')]
    public string $phoneNumber = '';

    #[Validate] // All rules are in rules() because uniqueness is dynamic.
    public string $email = '';

    public function render(): Factory|View
    {
        return view('components.admin-external-user-editor');
    }

    #[On('open-external-user-editor')]
    public function open(int $externalUserId): void
    {
        $this->ensureAuthenticated();
        $this->resetValidation();
        $this->externalUserId = $externalUserId;
        $this->fillFromExternalUser(ExternalUser::query()->findOrFail($externalUserId));
        $this->captureEditorSnapshot();
        $this->modalOpen = true;

        Flux::modal($this->modalName())->show();
    }

    public function close(): void
    {
        $this->reset();
        $this->resetValidation();

        Flux::modal($this->modalName())->close();
    }

    public function persist(): void
    {
        resolve(SaveExternalUserAction::class)(ExternalUser::query()->findOrFail($this->externalUserId), [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address' => $this->address,
            'zip_code' => $this->zipCode,
            'city' => $this->city,
            'country_of_residence' => $this->countryOfResidence,
            'phone_number' => new PhoneNumber($this->phoneNumber)->formatInternational(),
            'email' => $this->email,
        ]);

        $this->dispatch('external-user-saved');
        Flux::toast(heading: 'Gespeichert', text: 'Person wurde aktualisiert.', variant: 'success');
    }

    public function modalName(): string
    {
        return 'admin-external-user-editor';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'firstName' => trim($this->firstName),
            'lastName' => trim($this->lastName),
            'address' => trim($this->address),
            'zipCode' => trim($this->zipCode),
            'city' => trim($this->city),
            'countryOfResidence' => $this->countryOfResidence,
            'phoneNumber' => $this->normalizedPhoneNumber(),
            'email' => trim(mb_strtolower($this->email)),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fieldLabels(): array
    {
        return [
            'firstName' => 'Vorname',
            'lastName' => 'Nachname',
            'address' => 'Adresse',
            'zipCode' => 'PLZ',
            'city' => 'Ort',
            'countryOfResidence' => 'Wohnsitzland',
            'phoneNumber' => 'Telefon',
            'email' => 'E-Mail',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function logContext(): array
    {
        return ['external_user_id' => $this->externalUserId];
    }

    protected function normalizedPhoneNumber(): string
    {
        try {
            return new PhoneNumber($this->phoneNumber)->formatInternational();
        } catch (Throwable) {
            return $this->phoneNumber;
        }
    }

    protected function prepareValidation(): void
    {
        $this->email = trim(mb_strtolower($this->email));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'zipCode' => ['required', 'string', new ValidZipCode($this->countryOfResidence)],
            'email' => ['required', 'email', 'max:255', Rule::unique(ExternalUser::class, 'email')->ignore($this->externalUserId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'zipCode.required' => 'Bitte gib eine Postleitzahl ein.',
            'zipCode.string' => 'Die Postleitzahl muss ein Text sein.',
            'email.required' => 'Bitte gib eine E-Mail-Adresse ein.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'email.max' => 'Die E-Mail-Adresse darf nicht länger als 255 Zeichen sein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
        ];
    }

    protected function fillFromExternalUser(ExternalUser $externalUser): void
    {
        $this->firstName = $externalUser->first_name;
        $this->lastName = $externalUser->last_name;
        $this->address = $externalUser->address;
        $this->zipCode = $externalUser->zip_code;
        $this->city = $externalUser->city;
        $this->countryOfResidence = $externalUser->country_of_residence;
        $this->phoneNumber = $externalUser->phone_number;
        $this->email = $externalUser->email;
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::guard('web')->check(), 403);
    }
}
