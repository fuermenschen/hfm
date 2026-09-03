<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Mail\DonorInvoiceMail;
use App\Models\DonorEventInvoice;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendDonorInvoiceReminderAction
{
    public function __construct(private WeblingInvoiceService $weblingInvoices) {}

    public function __invoke(DonorEventInvoice $invoice): void
    {
        $invoice->loadMissing(['externalUser', 'donationEvent']);

        throw_if($invoice->invoice_sent_at === null, DonorInvoiceGuardException::class, 'Die Rechnung wurde noch nicht versendet.');
        throw_if($invoice->remote_deleted_at !== null, DonorInvoiceGuardException::class, 'Die Rechnung wurde in Webling gelöscht und kann nicht gemahnt werden.');
        throw_if($invoice->webling_debitor_id === null, DonorInvoiceGuardException::class, 'Die Rechnung wurde noch nicht in Webling erstellt.');

        // Live Webling read: fail closed when Webling is unavailable.
        try {
            $details = $this->weblingInvoices->invoiceDetails($invoice->webling_debitor_id);
        } catch (WeblingApiException $weblingApiException) {
            throw_if($weblingApiException->category !== WeblingApiException::NotFound, $weblingApiException);

            $invoice->markRemotelyDeleted();

            throw new DonorInvoiceGuardException('Die Rechnung wurde in Webling gelöscht und kann nicht gemahnt werden.', $weblingApiException->getCode(), $weblingApiException);
        }

        throw_if($details['state'] === 'paid' || $details['state'] === 'writeoff', DonorInvoiceGuardException::class, 'Die Rechnung ist bereits bezahlt oder abgeschrieben.');

        throw_unless(in_array($details['state'], ['open', 'partially paid'], true), DonorInvoiceGuardException::class, 'Der Webling-Status der Rechnung ist unbekannt.');

        $today = Date::now($invoice->donationEvent->timezone)->startOfDay();
        throw_if($details['due_date'] === null || Date::parse($details['due_date'])->startOfDay()->gte($today), DonorInvoiceGuardException::class, 'Die Rechnung ist noch nicht fällig.');

        $email = $invoice->externalUser->email;
        throw_if(trim($email) === '', DonorInvoiceGuardException::class, 'Der Spender hat keine gültige E-Mail-Adresse hinterlegt.');

        $disk = $invoice->pdf_disk !== null ? Storage::disk($invoice->pdf_disk) : null;
        throw_if($invoice->pdf_path === null || $disk === null || ! $disk->exists($invoice->pdf_path), DonorInvoiceGuardException::class, 'Es ist keine PDF-Datei für diese Rechnung vorhanden.');

        $amount = number_format($invoice->source_total_cents / 100, 2, '.', '');
        $dueDateText = Date::parse($details['due_date'])->format('d.m.Y');

        $body = 'Liebe:r '.$invoice->externalUser->first_name."\n\n"
            .'Wir erinnern dich an die noch offene Rechnung über Fr. '.$amount." für den Höhenmeter für Menschen.\n"
            .'Die Zahlung ist seit dem '.$dueDateText." fällig.\n"
            ."\nHerzliche Grüsse\nDas Team von Höhenmeter für Menschen";

        Mail::to(trim($email))->queue(new DonorInvoiceMail(
            subject: 'Erinnerung: Rechnung Höhenmeter für Menschen',
            body: $body,
            storageAttachments: [[
                'disk' => $invoice->pdf_disk,
                'path' => $invoice->pdf_path,
                'name' => sprintf('invoice_DON-%d-%d.pdf', $invoice->donation_event_id, $invoice->external_user_id),
                'mime' => 'application/pdf',
            ]],
        ));

        $invoice->forceFill(['invoice_reminder_sent_at' => Date::now()])->save();
    }
}
