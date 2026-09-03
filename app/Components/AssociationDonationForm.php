<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\CreateAssociationDonationInvoiceAction;
use App\Notifications\AssociationDonationInvoice;
use Exception;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

class AssociationDonationForm extends Component
{
    use UsesSpamProtection;

    public HoneypotData $extraFields;

    // Name der Firma (optional)
    #[Validate('nullable')]
    #[Validate('string', message: 'Der Name der Firma muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Name der Firma darf nicht länger als 255 Zeichen sein.')]
    public ?string $company_name = null;

    // Vorname
    #[Validate('required', message: 'Wir benötigen deinen Vornamen.')]
    #[Validate('string', message: 'Der Vorname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Vorname darf nicht länger als 255 Zeichen sein.')]
    public ?string $first_name = null;

    // Nachname
    #[Validate('required', message: 'Wir benötigen deinen Nachnamen.')]
    #[Validate('string', message: 'Der Nachname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Nachname darf nicht länger als 255 Zeichen sein.')]
    public ?string $last_name = null;

    // Adresse
    #[Validate('required', message: 'Wir benötigen deine Adresse.')]
    #[Validate('string', message: 'Die Adresse muss ein Text sein.')]
    #[Validate('max:255', message: 'Die Adresse darf nicht länger als 255 Zeichen sein.')]
    public ?string $address = null;

    // PLZ
    #[Validate('required', message: 'Wir benötigen deine Postleitzahl.')]
    #[Validate('integer', message: 'Die Postleitzahl muss eine Zahl sein.')]
    #[Validate('digits:4', message: 'Die Postleitzahl muss vier Ziffern haben.')]
    public ?int $zip_code = null;

    // Ort
    #[Validate('required', message: 'Wir benötigen deinen Wohnort.')]
    #[Validate('string', message: 'Der Wohnort muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Wohnort darf nicht länger als 255 Zeichen sein.')]
    public ?string $city = null;

    // E-Mail
    #[Validate('required', message: 'Wir benötigen deine E-Mail-Adresse.')]
    #[Validate('email', message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    public ?string $email = null;

    // E-Mail bestätigen
    #[Validate('required', message: 'Wir benötigen die Bestätigung deiner E-Mail-Adresse.')]
    #[Validate('same:email', message: 'Die E-Mail-Adressen stimmen nicht überein.')]
    public ?string $email_confirmation = null;

    // Betrag
    #[Validate('nullable')]
    #[Validate('numeric', message: 'Der Betrag muss eine Zahl sein.')]
    #[Validate('min:0', message: 'Der Betrag muss positiv sein.')]
    public ?float $amount = null;

    public function render(): Factory|View
    {
        return view('forms.association-donation-form');
    }

    public function submit(CreateAssociationDonationInvoiceAction $createAssociationDonationInvoiceAction): void
    {
        $this->protectAgainstSpam();

        try {
            $this->validate();
        } catch (ValidationException $validationException) {

            if ($validationException->validator->errors()->count() > 1) {
                $title = 'Es sind '.$validationException->validator->errors()->count().' Fehler aufgetreten.';
                $description = implode('<br>', $validationException->validator->errors()->all());
            } else {
                $title = $validationException->validator->errors()->first();
                $description = 'Bitte überprüfe deine Angaben.';
            }

            Flux::toast(heading: $title, text: $description, variant: 'danger');

            return;
        }

        try {

            $invoice = $createAssociationDonationInvoiceAction(
                first_name: $this->first_name,
                last_name: $this->last_name,
                company_name: $this->company_name,
                address: $this->address,
                zip_code: $this->zip_code,
                city: $this->city,
                amount: $this->amount,
            );

            $notification = new AssociationDonationInvoice(
                firstName: $this->first_name,
                pdfBase64: base64_encode($invoice['pdf']->output()),
                filename: $invoice['filename'],
            );

            Notification::route('mail', $this->email)->notify($notification);

        } catch (Exception $exception) {

            Flux::toast(heading: 'Fehler', text: 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.', variant: 'danger');

            $this->reset('email');

            return;
        }

        Flux::toast(heading: 'E-Mail versendet', text: 'Danke für deine Nachricht. Wir melden uns bald bei dir.', variant: 'success');

        $this->redirectHelper();

        $this->reset();
    }

    public function redirectHelper(): void
    {
        $this->redirect(route('home'), navigate: true);
    }

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;
    }
}
