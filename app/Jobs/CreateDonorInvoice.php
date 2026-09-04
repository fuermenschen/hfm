<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CollectDonorInvoiceDataAction;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\Letter\Dto\LetterOptions;
use App\Services\Webling\Letter\Dto\QrInvoiceOptions;
use App\Services\Webling\Letter\LetterService;
use App\Settings\InvoiceSettings;
use App\Settings\WeblingApiSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CreateDonorInvoice implements ShouldQueue
{
    use Queueable;

    public function __construct(public DonorEventInvoice $invoice) {}

    public function handle(
        CollectDonorInvoiceDataAction $collectInvoiceData,
        WeblingInvoiceService $weblingInvoices,
        LetterService $letters,
        InvoiceSettings $invoiceSettings,
        WeblingApiSettings $weblingSettings,
    ): void {
        Cache::lock('donor-invoice-creation:'.$this->invoice->id, 120)->block(10, function () use ($collectInvoiceData, $weblingInvoices, $letters, $invoiceSettings, $weblingSettings): void {
            $invoice = DonorEventInvoice::query()
                ->with(['externalUser', 'donationEvent'])
                ->findOrFail($this->invoice->id);

            if ($invoice->webling_debitor_id !== null && $invoice->pdf_disk !== null && $invoice->pdf_path !== null) {
                return;
            }

            $snapshot = $invoice->source_snapshot;
            if ($snapshot === null) {
                $snapshot = $this->createSnapshot($invoice, $collectInvoiceData, $invoiceSettings, $weblingSettings);
                $invoice->forceFill([
                    'source_snapshot' => $snapshot,
                    'source_total_cents' => $snapshot['total_cents'],
                ])->save();
            }

            $debitorId = $invoice->webling_debitor_id;
            if ($debitorId === null) {
                $marker = $weblingInvoices->commentMarker($invoice->id);
                $matchingIds = $weblingInvoices->findInvoiceIdsByCommentMarker($marker);

                if (count($matchingIds) > 1) {
                    throw new RuntimeException('Multiple Webling Debitors match local invoice ID '.$invoice->id.'.');
                }

                $debitorId = $matchingIds[0] ?? $this->createDebitor($weblingInvoices, $invoice->id, $snapshot);

                $invoice->forceFill([
                    'webling_debitor_id' => $debitorId,
                    // Same-row recreation after remote deletion: this row is live again.
                    'remote_deleted_at' => null,
                ])->save();
            }

            if ($invoice->pdf_disk !== null && $invoice->pdf_path !== null) {
                return;
            }

            $response = $letters->createFromSnapshot($snapshot, (string) $snapshot['title'], $debitorId);
            if (! $response->successful()) {
                throw new RuntimeException('Webling letter creation failed for local invoice ID '.$invoice->id.'.');
            }

            $pdf = $response->body();
            if ($pdf === '') {
                throw new RuntimeException('Webling returned an empty invoice PDF for local invoice ID '.$invoice->id.'.');
            }

            $path = 'webling/donor-invoices/'.$invoice->id.'/'.Str::uuid().'.pdf';
            if (! Storage::disk('local')->put($path, $pdf)) {
                throw new RuntimeException('Invoice PDF storage failed for local invoice ID '.$invoice->id.'.');
            }

            $invoice->forceFill([
                'pdf_disk' => 'local',
                'pdf_path' => $path,
            ])->save();
        });
    }

    /**
     * @return array{title:string,total_cents:int,lines:list<array{athlete:string,partner:?string,rounds:int,amount_per_round_cents:int,subtotal_cents:int,min_cents:?int,max_cents:?int,total_cents:int}>,webling:array<string,mixed>,letter:array<string,mixed>,template_version:string}
     */
    protected function createSnapshot(
        DonorEventInvoice $invoice,
        CollectDonorInvoiceDataAction $collectInvoiceData,
        InvoiceSettings $invoiceSettings,
        WeblingApiSettings $weblingSettings,
    ): array {
        $externalUser = $invoice->externalUser;
        $donationEvent = $invoice->donationEvent;
        $lines = $collectInvoiceData($invoice);
        $weblingLines = array_values(array_filter(array_map(fn (array $line): array => [
            'amount_cents' => $line['total_cents'],
            'title' => $this->invoiceLineTitle($line),
        ], $lines), fn (array $line): bool => $line['amount_cents'] > 0));

        if ($weblingLines === []) {
            throw new RuntimeException('No billable invoice lines for external user ID '.$invoice->external_user_id.'.');
        }

        $invoiceDate = Date::now($donationEvent->timezone)->startOfDay();
        $dueDate = $invoiceDate->copy()->addDays($invoiceSettings->due_days > 0 ? $invoiceSettings->due_days : 14);
        $title = 'Spendenrechnung Höhenmeter für Menschen';
        $totalCents = array_sum(array_column($weblingLines, 'amount_cents'));
        $addressLines = $this->addressLines($externalUser);
        $amount = number_format($totalCents / 100, 2, '.', '');
        $dueDateText = $dueDate->format('d.m.Y');
        $additionalInformation = $donationEvent->contentValue('invoice.additional_information', $title) ?? $title;

        $qr = new QrInvoiceOptions(
            iban: $invoiceSettings->qr_iban,
            creditorName: $invoiceSettings->creditor_name,
            creditorStreet: $invoiceSettings->creditor_street,
            creditorBuildingNumber: $invoiceSettings->creditor_building_number,
            creditorPostalCode: $invoiceSettings->creditor_postal_code,
            creditorCity: $invoiceSettings->creditor_city,
            debtorName: [trim($externalUser->first_name.' '.$externalUser->last_name)],
            debtorStreet: $externalUser->address !== '' ? [$externalUser->address] : [],
            debtorPostalCode: $externalUser->zip_code !== '' ? [$externalUser->zip_code] : [],
            debtorCity: $externalUser->city !== '' ? [$externalUser->city] : [],
            additionalInformation: $additionalInformation,
            withAmount: $invoiceSettings->qr_show_amount,
        );

        return [
            'title' => $title,
            'total_cents' => $totalCents,
            'lines' => $lines,
            'webling' => [
                'title' => $title,
                'date' => $invoiceDate->toDateString(),
                'duedate' => $dueDate->toDateString(),
                'address_lines' => $addressLines,
                'period_id' => $weblingSettings->accounting_period_id,
                'accounting_period_id' => $weblingSettings->accounting_period_id,
                'debit_account_id' => $weblingSettings->debit_account_id,
                'credit_account_id' => $weblingSettings->credit_account_id,
                'invoice_lines' => $weblingLines,
            ],
            'letter' => [
                'header_text' => '',
                'body_intro' => 'Liebe:r '.$externalUser->first_name."\n\nWir schätzen dein Engagement sehr und möchten dir herzlich danken.\nUntenstehend findest du eine Übersicht über deine Spenden.\n",
                'body_outro' => 'Bitte verwende zur Einzahlung den beiliegenden Einzahlungsschein. Die Zahlung des Betrags von mindestens Fr. '.$amount.' ist fällig bis am '.$dueDateText.'. Nach Eingang aller Spenden werden wir die Überweisungen an die drei Benefizpartner:innen vornehmen. Wir werden dich informieren, wann wir welche Beträge überweisen durften.'."\n\nHerzliche Grüsse\nDas Team von Höhenmeter für Menschen",
                'date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'qr_invoice' => $qr->toArray(),
                'options' => (new LetterOptions)->toArray(),
            ],
            'template_version' => 'invoice-letter-v1',
        ];
    }

    /**
     * @param  array<string,mixed>  $snapshot
     */
    protected function createDebitor(WeblingInvoiceService $weblingInvoices, int $invoiceId, array $snapshot): int
    {
        $webling = $snapshot['webling'] ?? null;
        throw_unless(is_array($webling), RuntimeException::class, 'Local invoice snapshot is missing Webling data.');

        $response = $weblingInvoices->createInvoiceWithMarker($invoiceId, InvoiceCreateData::fromArray($webling));
        throw_unless($response->successful(), RuntimeException::class, 'Webling Debitor creation failed for local invoice ID '.$invoiceId.'.');

        $id = $this->responseId($response);
        throw_if($id < 1, RuntimeException::class, 'Webling returned no valid Debitor ID for local invoice ID '.$invoiceId.'.');

        return $id;
    }

    protected function responseId(Response $response): int
    {
        $data = $response->json();

        if (is_array($data)) {
            $data = $data['id'] ?? null;
        }

        return is_int($data) || (is_string($data) && ctype_digit($data)) ? (int) $data : 0;
    }

    /**
     * @param  array{athlete:string,partner:?string,rounds:int,amount_per_round_cents:int,subtotal_cents:int,min_cents:?int,max_cents:?int,total_cents:int}  $line
     */
    protected function invoiceLineTitle(array $line): string
    {
        $title = sprintf(
            '%s für %s | %d Runden à Fr. %s',
            $line['athlete'],
            $line['partner'] ?? '',
            $line['rounds'],
            number_format($line['amount_per_round_cents'] / 100, 2, '.', ''),
        );

        if ($line['subtotal_cents'] > $line['total_cents']) {
            return $title.' | Max. Fr. '.number_format($line['total_cents'] / 100, 2, '.', '');
        }

        if ($line['subtotal_cents'] < $line['total_cents']) {
            return $title.' | Min. Fr. '.number_format($line['total_cents'] / 100, 2, '.', '');
        }

        return $title;
    }

    /**
     * @return list<string>
     */
    protected function addressLines(ExternalUser $externalUser): array
    {
        $country = mb_strtoupper($externalUser->country_of_residence);
        $zip = $externalUser->zip_code;

        if ($country !== '' && $country !== 'CH' && ! str_starts_with(mb_strtoupper($zip), $country.'-')) {
            $zip = $country.'-'.ltrim(mb_strtoupper($zip));
        }

        return array_values(array_filter([
            trim($externalUser->first_name.' '.$externalUser->last_name),
            $externalUser->address,
            trim($zip.' '.$externalUser->city),
        ], fn (string $line): bool => $line !== ''));
    }
}
