<?php

namespace App\Components;

use App\Actions\CreateAssociationDonationInvoice;
use Exception;
use Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use WireUi\Traits\Actions;

class AdminAssociationDonationInvoiceForm extends Component
{
    use Actions;

    // Name der Firma (optional)
    #[Validate('nullable')]
    #[Validate('string', message: 'Der Name der Firma muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Name der Firma darf nicht länger als 255 Zeichen sein.')]
    public ?string $company_name = null;

    // Vorname
    #[Validate('required', message: 'Wir benötigen einen Vornamen.')]
    #[Validate('string', message: 'Der Vorname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Vorname darf nicht länger als 255 Zeichen sein.')]
    public ?string $first_name = null;

    // Nachname
    #[Validate('required', message: 'Wir benötigen einen Nachnamen.')]
    #[Validate('string', message: 'Der Nachname muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Nachname darf nicht länger als 255 Zeichen sein.')]
    public ?string $last_name = null;

    // Adresse
    #[Validate('required', message: 'Wir benötigen eine Adresse.')]
    #[Validate('string', message: 'Die Adresse muss ein Text sein.')]
    #[Validate('max:255', message: 'Die Adresse darf nicht länger als 255 Zeichen sein.')]
    public ?string $address = null;

    // PLZ
    #[Validate('required', message: 'Wir benötigen eine Postleitzahl.')]
    #[Validate('integer', message: 'Die Postleitzahl muss eine Zahl sein.')]
    #[Validate('digits:4', message: 'Die Postleitzahl muss vier Ziffern haben.')]
    public ?int $zip_code = null;

    // Ort
    #[Validate('required', message: 'Wir benötigen einen Wohnort.')]
    #[Validate('string', message: 'Der Wohnort muss ein Text sein.')]
    #[Validate('max:255', message: 'Der Wohnort darf nicht länger als 255 Zeichen sein.')]
    public ?string $city = null;

    // Betrag
    #[Validate('nullable')]
    #[Validate('numeric', message: 'Der Betrag muss eine Zahl sein.')]
    #[Validate('min:0.05', message: 'Der Betrag muss mindestens fünf Rappen betragen.')]
    public ?float $amount = null;

    public function render()
    {
        return view('forms.admin.association-donation-invoice-form');
    }

    public function submit()
    {

        $response = response();

        try {
            $this->validate();
        } catch (ValidationException $e) {

            Flux::toast(variant: 'danger', heading: 'Fehler', text: 'Bitte überprüfe deine Eingaben.');

            return $response;
        }

        try {

            $invoice = CreateAssociationDonationInvoice::run(
                first_name: $this->first_name,
                last_name: $this->last_name,
                address: $this->address,
                zip_code: $this->zip_code,
                city: $this->city,
                company_name: $this->company_name,
                amount: $this->amount
            );

            // download the invoice
            $pdf = $invoice['pdf'];
            $filename = $invoice['filename'];

            $response = response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, $filename);

        } catch (Exception $e) {

            Flux::toast(variant: 'danger', heading: 'Fehler', text: 'Es gab einen Fehler beim Erstellen der Rechnung. Bitte versuche es später erneut.');

            return $response;
        }

        Flux::toast(variant: 'success', heading: 'Erfolg', text: 'Die Rechnung wurde erfolgreich erstellt.');
        Flux::modal('create-association-donation-invoice')->close();

        $this->reset();

        return $response;
    }

    public function redirectHelper(): void
    {
        $this->redirect(route('home'), navigate: true);
    }
}
