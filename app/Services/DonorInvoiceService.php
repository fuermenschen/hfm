<?php

namespace App\Services;

use App\Jobs\CreateDonorInvoice;
use App\Jobs\DeleteDonorInvoiceDebitor;
use App\Mail\GenericMailMessage;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DonorInvoiceService
{
    /**
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    public function createInvoice(Donor $donor): array
    {
        $weblingData = $donor->webling_data ?? [];
        $hasDebitor = ! empty($weblingData['debitor_id']);
        $hasLetterPdf = ! empty($weblingData['letter_pdf']);

        if ($hasDebitor && $hasLetterPdf) {
            return [
                'heading' => 'Bereits vorhanden',
                'text' => 'Für '.$donor->privacy_name.' ist bereits eine Rechnung erstellt worden. Es gibt nichts zu tun.',
                'variant' => 'warning',
                'duration' => null,
                'refresh' => false,
            ];
        }

        CreateDonorInvoice::dispatchSync($donor);

        return [
            'heading' => 'Rechnung erstellt',
            'text' => 'Die Rechnung für '.$donor->privacy_name.' wurde erfolgreich erstellt.',
            'variant' => 'success',
            'duration' => null,
            'refresh' => true,
        ];
    }

    /**
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    public function deleteInvoice(Donor $donor): array
    {
        $weblingData = $donor->webling_data ?? [];
        $hasDebitor = ! empty($weblingData['debitor_id']);
        $hasLetterPdf = ! empty($weblingData['letter_pdf']);

        if (! $hasDebitor && ! $hasLetterPdf) {
            return [
                'heading' => 'Nichts zu löschen',
                'text' => 'Für '.$donor->privacy_name.' sind keine Rechnungseinträge vorhanden.',
                'variant' => 'warning',
                'duration' => null,
                'refresh' => false,
            ];
        }

        DeleteDonorInvoiceDebitor::dispatchSync($donor);

        return [
            'heading' => 'Rechnung gelöscht',
            'text' => 'Die Rechnungseinträge für '.$donor->privacy_name.' wurden gelöscht.',
            'variant' => 'success',
            'duration' => null,
            'refresh' => true,
        ];
    }

    /**
     * @return array{absolute_path:string,file_name:string}|null
     */
    public function getDownloadData(Donor $donor): ?array
    {
        $letterPdf = $this->letterPdfData($donor);
        if (! is_array($letterPdf)) {
            return null;
        }

        $disk = (string) ($letterPdf['disk'] ?? 'local');
        $path = (string) ($letterPdf['path'] ?? '');
        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $donId = $this->donorPublicId($donor);

        return [
            'absolute_path' => Storage::disk($disk)->path($path),
            'file_name' => 'Rechnung_'.$donId.'.pdf',
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function letterPdfData(Donor $donor): ?array
    {
        $weblingData = $donor->webling_data ?? [];

        if (! isset($weblingData['letter_pdf']) || ! is_array($weblingData['letter_pdf'])) {
            return null;
        }

        return $weblingData['letter_pdf'];
    }

    protected function donorPublicId(Donor $donor): string
    {
        return 'DON-'.sprintf('25%04d', $donor->id);
    }

    /**
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    public function sendInvoice(Donor $donor): array
    {
        if (empty($donor->email)) {
            return [
                'heading' => 'Keine E-Mail-Adresse',
                'text' => 'Für '.$donor->privacy_name.' ist keine E-Mail-Adresse hinterlegt.',
                'variant' => 'danger',
                'duration' => 0,
                'refresh' => false,
            ];
        }

        $letterPdf = $this->letterPdfData($donor);
        if (! is_array($letterPdf)) {
            return [
                'heading' => 'Kein PDF gefunden',
                'text' => 'Für '.$donor->privacy_name.' ist noch kein Rechnungs-PDF vorhanden.',
                'variant' => 'danger',
                'duration' => 0,
                'refresh' => false,
            ];
        }

        $disk = (string) ($letterPdf['disk'] ?? 'local');
        $path = (string) ($letterPdf['path'] ?? '');
        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            return [
                'heading' => 'Datei nicht gefunden',
                'text' => 'Das gespeicherte Rechnungs-PDF konnte nicht gefunden werden.',
                'variant' => 'danger',
                'duration' => 0,
                'refresh' => false,
            ];
        }

        $donId = $this->donorPublicId($donor);
        $fileName = 'Rechnung_'.$donId.'.pdf';

        $subject = 'Rechnung Höhenmeter für Menschen';
        $html = '<p>Liebe:r '.$donor->first_name.'</p>'
            .'<p>Im Anhang findest du deine Rechnung. Vielen Dank für deine Unterstützung!</p>'
            .'<p>Herzliche Grüsse<br>Das Team von Höhenmeter für Menschen</p>';

        $mailable = new GenericMailMessage(
            subject: $subject,
            html: $html,
            storageAttachments: [[
                'disk' => $disk,
                'path' => $path,
                'name' => $fileName,
                'mime' => 'application/pdf',
            ]]
        );

        Mail::to($donor)->queue($mailable);

        $donor->invoice_sent_at = now();
        $donor->save();

        return [
            'heading' => 'Rechnung gesendet',
            'text' => 'Die Rechnung wurde an '.$donor->email.' gesendet.',
            'variant' => 'success',
            'duration' => null,
            'refresh' => true,
        ];
    }

    /**
     * @return array{heading:string,text:string,variant:string,duration:int|null,refresh:bool}
     */
    public function sendReminder(Donor $donor): array
    {
        if (empty($donor->invoice_sent_at)) {
            return [
                'heading' => 'Rechnung nicht gesendet',
                'text' => 'Die Rechnung wurde für '.$donor->privacy_name.' noch nicht gesendet.',
                'variant' => 'danger',
                'duration' => 0,
                'refresh' => false,
            ];
        }

        $paymentStatus = data_get($donor->webling_data, 'payment_status');
        if ($paymentStatus !== 'overdue') {
            return [
                'heading' => 'Nicht überfällig',
                'text' => 'Die Rechnung von '.$donor->privacy_name.' ist nicht als überfällig markiert.',
                'variant' => 'warning',
                'duration' => null,
                'refresh' => false,
            ];
        }

        if (empty($donor->email)) {
            return [
                'heading' => 'Keine E-Mail-Adresse',
                'text' => 'Für '.$donor->privacy_name.' ist keine E-Mail-Adresse hinterlegt.',
                'variant' => 'danger',
                'duration' => 0,
                'refresh' => false,
            ];
        }

        $letterPdf = $this->letterPdfData($donor);
        if (! is_array($letterPdf)) {
            return [
                'heading' => 'Kein PDF gefunden',
                'text' => 'Für '.$donor->privacy_name.' ist kein Rechnungs-PDF vorhanden.',
                'variant' => 'danger',
                'duration' => 0,
                'refresh' => false,
            ];
        }

        $disk = (string) ($letterPdf['disk'] ?? 'local');
        $path = (string) ($letterPdf['path'] ?? '');
        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            return [
                'heading' => 'Datei nicht gefunden',
                'text' => 'Das gespeicherte Rechnungs-PDF konnte nicht gefunden werden.',
                'variant' => 'danger',
                'duration' => 0,
                'refresh' => false,
            ];
        }

        $donId = $this->donorPublicId($donor);
        $fileName = 'Rechnung_'.$donId.'.pdf';

        $subject = 'Zahlungserinnerung – Höhenmeter für Menschen';
        $html = '<p>Liebe:r '.$donor->first_name.'</p>'
            .'<p>Wir möchten dich freundlich an die offene Spendenrechnung erinnern. Im Anhang findest du die Rechnung nochmals. Der Versand der Rechnung erfolgte am '.Carbon::parse($donor->invoice_sent_at)->format('d.m.Y').'.</p>'
            .'<p>Sollte sich diese Erinnerung mit deiner Zahlung gekreuzt haben, kannst du diese Nachricht ignorieren.</p>'
            .'<p>Vielen Dank und herzliche Grüsse<br>Das Team von Höhenmeter für Menschen</p>';

        $mailable = new GenericMailMessage(
            subject: $subject,
            html: $html,
            storageAttachments: [[
                'disk' => $disk,
                'path' => $path,
                'name' => $fileName,
                'mime' => 'application/pdf',
            ]]
        );

        Mail::to($donor)->queue($mailable);

        $donor->invoice_reminder_sent_at = now();
        $donor->save();

        return [
            'heading' => 'Zahlungserinnerung gesendet',
            'text' => 'Die Zahlungserinnerung wurde an '.$donor->email.' gesendet.',
            'variant' => 'success',
            'duration' => null,
            'refresh' => true,
        ];
    }

    public function formatInvoiceStatus(Donor $donor): string
    {
        $weblingData = $donor->webling_data ?? [];
        $payment = $weblingData['payment_status'] ?? null;

        if ($payment === 'paid') {
            return 'bezahlt';
        }

        if ($payment === 'overdue') {
            return 'überfällig';
        }

        if (! empty($donor->invoice_sent_at)) {
            return 'gesendet';
        }

        if (! empty($weblingData['letter_pdf'])) {
            return 'erstellt';
        }

        return '-';
    }

    public function canCreateInvoiceInBulk(Donor $donor): bool
    {
        return $donor->donations()->exists();
    }

    public function canSendInvoiceInBulk(Donor $donor): bool
    {
        return empty($donor->invoice_sent_at);
    }

    public function canSendReminderInBulk(Donor $donor): bool
    {
        $paymentStatus = data_get($donor->webling_data, 'payment_status');

        return empty($donor->invoice_reminder_sent_at)
            && ! empty($donor->invoice_sent_at)
            && $paymentStatus === 'overdue';
    }

    public function invoiceTotalSubquery(): Builder
    {
        return $this->invoiceTotalQuery()
            ->whereColumn('donations.donor_id', 'donors.id');
    }

    public function invoiceTotalForDonor(Donor $donor): float
    {
        $precomputedInvoiceTotal = $donor->getAttribute('invoice_total');

        if (is_numeric($precomputedInvoiceTotal)) {
            return round((float) $precomputedInvoiceTotal, 2);
        }

        $invoiceTotal = $this->invoiceTotalQuery()
            ->where('donations.donor_id', $donor->id)
            ->value('invoice_total');

        return round((float) $invoiceTotal, 2);
    }

    public function invoiceStatusCaseSql(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $paymentExpr = "JSON_UNQUOTE(JSON_EXTRACT(webling_data, '$.payment_status'))";
            $letterExpr = "JSON_EXTRACT(webling_data, '$.letter_pdf')";
        } else {
            $paymentExpr = "json_extract(webling_data, '$.payment_status')";
            $letterExpr = "json_extract(webling_data, '$.letter_pdf')";
        }

        return "CASE\n"
            ."                WHEN {$paymentExpr} = 'paid' THEN 'bezahlt'\n"
            ."                WHEN {$paymentExpr} = 'overdue' THEN 'überfällig'\n"
            ."                WHEN invoice_sent_at IS NOT NULL THEN 'gesendet'\n"
            ."                WHEN {$letterExpr} IS NOT NULL THEN 'erstellt'\n"
            ."                ELSE '-'\n"
            .'                END AS invoice_status';
    }

    protected function invoiceTotalQuery(): Builder
    {
        return Donation::query()
            ->join('athletes', 'athletes.id', '=', 'donations.athlete_id')
            ->selectRaw('COALESCE(SUM('.$this->invoiceLineTotalSql().'), 0) AS invoice_total');
    }

    protected function invoiceLineTotalSql(): string
    {
        $subtotal = '(COALESCE(athletes.rounds_done, 0) * COALESCE(donations.amount_per_round, 0))';

        return 'ROUND(CASE '
            .'WHEN donations.amount_min IS NOT NULL AND '.$subtotal.' < donations.amount_min THEN donations.amount_min '
            .'WHEN donations.amount_max IS NOT NULL AND '.$subtotal.' > donations.amount_max THEN donations.amount_max '
            .'ELSE '.$subtotal.' '
            .'END, 2)';
    }

    /**
     * @return array{paid:int,overdue:int,sent:int,created:int,not_created:int}
     */
    public function invoiceStatusSummary(): array
    {
        $paid = Donor::query()
            ->where('webling_data->payment_status', 'paid')
            ->count();

        $overdue = Donor::query()
            ->where('webling_data->payment_status', 'overdue')
            ->count();

        $sent = Donor::query()
            ->whereNotNull('invoice_sent_at')
            ->where(function ($query): void {
                $query->whereNull('webling_data->payment_status')
                    ->orWhereNotIn('webling_data->payment_status', ['paid', 'overdue']);
            })
            ->count();

        $created = Donor::query()
            ->whereNull('invoice_sent_at')
            ->whereNotNull('webling_data->letter_pdf')
            ->where(function ($query): void {
                $query->whereNull('webling_data->payment_status')
                    ->orWhereNotIn('webling_data->payment_status', ['paid', 'overdue']);
            })
            ->count();

        $notCreated = Donor::query()
            ->whereNull('invoice_sent_at')
            ->whereNull('webling_data->letter_pdf')
            ->where(function ($query): void {
                $query->whereNull('webling_data->payment_status')
                    ->orWhereNotIn('webling_data->payment_status', ['paid', 'overdue']);
            })
            ->count();

        return [
            'paid' => $paid,
            'overdue' => $overdue,
            'sent' => $sent,
            'created' => $created,
            'not_created' => $notCreated,
        ];
    }
}
