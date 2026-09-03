<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DonorInvoiceStatus;
use App\Exceptions\DonorInvoiceGuardException;
use App\Mail\DonorInvoiceMail;
use App\Models\DonorEventInvoice;
use App\Services\DonorInvoiceService;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendDonorInvoiceAction
{
    public function __construct(private DonorInvoiceService $donorInvoices) {}

    public function __invoke(DonorEventInvoice $invoice): void
    {
        $invoice->loadMissing(['externalUser', 'donationEvent']);

        throw_if($invoice->remote_deleted_at !== null, DonorInvoiceGuardException::class, 'Die Rechnung wurde in Webling gelöscht und kann nicht gesendet werden.');
        throw_if(! $invoice->donationEvent->hasEnded(), DonorInvoiceGuardException::class, 'Rechnungen können erst nach Anlassende versendet werden.');
        throw_if($invoice->webling_debitor_id === null, DonorInvoiceGuardException::class, 'Die Rechnung wurde noch nicht in Webling erstellt.');
        throw_if($this->donorInvoices->status($invoice) === DonorInvoiceStatus::Unknown, DonorInvoiceGuardException::class, 'Der Webling-Status der Rechnung ist unbekannt.');
        throw_if(in_array($this->donorInvoices->status($invoice), [DonorInvoiceStatus::Paid, DonorInvoiceStatus::Writeoff], true), DonorInvoiceGuardException::class, 'Bezahlte oder abgeschriebene Rechnungen können nicht gesendet werden.');

        $email = $invoice->externalUser->email;
        throw_if(trim($email) === '', DonorInvoiceGuardException::class, 'Der Spender hat keine gültige E-Mail-Adresse hinterlegt.');

        $disk = $invoice->pdf_disk !== null ? Storage::disk($invoice->pdf_disk) : null;
        throw_if($invoice->pdf_path === null || $disk === null || ! $disk->exists($invoice->pdf_path), DonorInvoiceGuardException::class, 'Es ist keine PDF-Datei für diese Rechnung vorhanden.');

        $snapshot = $invoice->source_snapshot ?? [];
        $totalCents = (int) ($snapshot['total_cents'] ?? $invoice->source_total_cents ?? 0);
        $dueDate = $snapshot['letter']['due_date'] ?? null;
        $amount = number_format($totalCents / 100, 2, '.', '');
        $dueDateText = is_string($dueDate) && $dueDate !== '' ? Date::parse($dueDate)->format('d.m.Y') : null;

        $body = 'Liebe:r '.$invoice->externalUser->first_name."\n\n"
            .'Im Anhang findest du deine Rechnung über Fr. '.$amount." für den Höhenmeter für Menschen.\n"
            .($dueDateText !== null ? 'Die Zahlung ist fällig bis am '.$dueDateText.".\n" : '')
            ."\nHerzliche Grüsse\nDas Team von Höhenmeter für Menschen";

        Mail::to(trim($email))->queue(new DonorInvoiceMail(
            subject: 'Rechnung Höhenmeter für Menschen',
            body: $body,
            storageAttachments: [[
                'disk' => $invoice->pdf_disk,
                'path' => $invoice->pdf_path,
                'name' => sprintf('invoice_DON-%d-%d.pdf', $invoice->donation_event_id, $invoice->external_user_id),
                'mime' => 'application/pdf',
            ]],
        ));

        $invoice->forceFill(['invoice_sent_at' => Date::now()])->save();
    }
}
