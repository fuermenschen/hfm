<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\CreateDonorInvoiceAction;
use App\Actions\DeleteDonorInvoiceAction;
use App\Actions\DownloadAthleteDocumentAction;
use App\Actions\DownloadAthleteDocumentArchiveAction;
use App\Actions\DownloadAthleteStoryImageArchiveAction;
use App\Actions\DownloadDonorInvoicePdfAction;
use App\Actions\DownloadEventInvoiceArchiveAction;
use App\Actions\RefreshDonorInvoiceStatusAction;
use App\Actions\RunDonorInvoiceBulkAction;
use App\Actions\SendDonorInvoiceAction;
use App\Actions\SendDonorInvoiceReminderAction;
use App\Enums\AthleteDocumentType;
use App\Enums\DonorInvoiceStatus;
use App\Exceptions\DonorInvoiceGuardException;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Services\AthleteService;
use App\Services\CurrentDonationEventService;
use App\Services\DonorInvoiceService;
use App\Services\DonorService;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Closure;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AdminPersonTable extends AbstractDatatableComponent
{
    #[Url]
    public string $sortField = 'first_name';

    #[Locked]
    public string $role = '';

    #[Url(as: 'anlass', except: '')]
    public ?string $eventSlug = '';

    public ?string $confirmingInvoiceAction = null;

    public ?int $confirmingInvoiceUserId = null;

    public int $bulkEligibleCount = 0;

    public int $bulkSkippedCount = 0;

    protected AthleteService $athleteService;

    protected DonorService $donorService;

    protected CurrentDonationEventService $currentDonationEventService;

    protected DownloadAthleteDocumentAction $downloadAthleteDocumentAction;

    protected DownloadAthleteDocumentArchiveAction $downloadAthleteDocumentArchiveAction;

    protected DownloadAthleteStoryImageArchiveAction $downloadAthleteStoryImageArchiveAction;

    protected CreateDonorInvoiceAction $createDonorInvoice;

    protected SendDonorInvoiceAction $sendDonorInvoice;

    protected SendDonorInvoiceReminderAction $sendDonorInvoiceReminder;

    protected DeleteDonorInvoiceAction $deleteDonorInvoice;

    protected RefreshDonorInvoiceStatusAction $refreshDonorInvoiceStatus;

    protected DownloadDonorInvoicePdfAction $downloadDonorInvoicePdf;

    protected DownloadEventInvoiceArchiveAction $downloadEventInvoiceArchive;

    protected RunDonorInvoiceBulkAction $runDonorInvoiceBulk;

    protected WeblingInvoiceService $weblingInvoices;

    protected DonorInvoiceService $donorInvoices;

    protected ?DonationEvent $resolvedInvoiceEvent = null;

    protected bool $resolvedInvoiceEventChecked = false;

    public function boot(
        AthleteService $athleteService,
        DonorService $donorService,
        CurrentDonationEventService $currentDonationEventService,
        DownloadAthleteDocumentAction $downloadAthleteDocumentAction,
        DownloadAthleteDocumentArchiveAction $downloadAthleteDocumentArchiveAction,
        DownloadAthleteStoryImageArchiveAction $downloadAthleteStoryImageArchiveAction,
        CreateDonorInvoiceAction $createDonorInvoice,
        SendDonorInvoiceAction $sendDonorInvoice,
        SendDonorInvoiceReminderAction $sendDonorInvoiceReminder,
        DeleteDonorInvoiceAction $deleteDonorInvoice,
        RefreshDonorInvoiceStatusAction $refreshDonorInvoiceStatus,
        DownloadDonorInvoicePdfAction $downloadDonorInvoicePdf,
        DownloadEventInvoiceArchiveAction $downloadEventInvoiceArchive,
        RunDonorInvoiceBulkAction $runDonorInvoiceBulk,
        WeblingInvoiceService $weblingInvoices,
        DonorInvoiceService $donorInvoices,
    ): void {
        $this->athleteService = $athleteService;
        $this->donorService = $donorService;
        $this->currentDonationEventService = $currentDonationEventService;
        $this->downloadAthleteDocumentAction = $downloadAthleteDocumentAction;
        $this->downloadAthleteDocumentArchiveAction = $downloadAthleteDocumentArchiveAction;
        $this->downloadAthleteStoryImageArchiveAction = $downloadAthleteStoryImageArchiveAction;
        $this->createDonorInvoice = $createDonorInvoice;
        $this->sendDonorInvoice = $sendDonorInvoice;
        $this->sendDonorInvoiceReminder = $sendDonorInvoiceReminder;
        $this->deleteDonorInvoice = $deleteDonorInvoice;
        $this->refreshDonorInvoiceStatus = $refreshDonorInvoiceStatus;
        $this->downloadDonorInvoicePdf = $downloadDonorInvoicePdf;
        $this->downloadEventInvoiceArchive = $downloadEventInvoiceArchive;
        $this->runDonorInvoiceBulk = $runDonorInvoiceBulk;
        $this->weblingInvoices = $weblingInvoices;
        $this->donorInvoices = $donorInvoices;
    }

    public function mount(string $role = ''): void
    {
        throw_unless(in_array($role, ['athlete', 'donor'], true), \InvalidArgumentException::class, 'Invalid person role.');

        $this->role = $role;

        if (! request()->query->has('anlass') && ($this->eventSlug === null || $this->eventSlug === '')) {
            $currentEvent = $this->currentDonationEventService->current();
            $this->eventSlug = $currentEvent instanceof DonationEvent ? $currentEvent->slug : '';
        }

        parent::mount();
    }

    public function updated(string $property): void
    {
        parent::updated($property);

        if ($property === 'eventSlug') {
            $this->resolvedInvoiceEvent = null;
            $this->resolvedInvoiceEventChecked = false;
            $this->confirmingInvoiceAction = null;
            $this->confirmingInvoiceUserId = null;
        }
    }

    protected function tableView(): string
    {
        return 'components.admin.tables.person-table';
    }

    protected function tableDataKey(): string
    {
        return 'external_users';
    }

    /**
     * @return array<int|string, string>
     */
    protected function searchableColumns(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'city',
            'country_of_residence',
            'public_id',
            'athleteRegistrations.partner.name',
        ];
    }

    protected function baseQuery(): Builder
    {
        if ($this->role === 'athlete') {
            $query = $this->athleteService->all()->with([
                'athleteRegistrations.donationEvent',
                'athleteRegistrations.partner',
                'athleteRegistrations.eventGroup',
            ]);
            $partnerQuery = AthleteRegistration::query()
                ->select('partners.name')
                ->join('partners', 'partners.id', '=', 'athlete_registrations.partner_id')
                ->whereColumn('athlete_registrations.external_user_id', 'external_users.id')
                ->limit(1);

            if ($this->eventSlug !== null && $this->eventSlug !== '') {
                $partnerQuery->whereHas('donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));
            }

            return $query->addSelect(['selected_partner_name' => $partnerQuery]);
        }

        $query = $this->donorService->all()->with('donationsAsDonor.athleteRegistration.donationEvent');

        $event = $this->selectedEvent();
        if ($event instanceof DonationEvent) {
            $query->with([
                'donorEventInvoices' => fn (Relation $invoiceQuery) => $invoiceQuery->where('donation_event_id', $event->id),
                'donorEventInvoices.donationEvent',
            ]);
        }

        return $query;
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->eventSlug === null || $this->eventSlug === '') {
            return;
        }

        if ($this->role === 'athlete') {
            $query->whereHas('athleteRegistrations.donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));

            return;
        }

        $query->whereHas('donationsAsDonor.athleteRegistration.donationEvent', fn (Builder $event): Builder => $event->where('slug', $this->eventSlug));
    }

    protected function tableFilterProperties(): array
    {
        return ['eventSlug'];
    }

    protected function defaultSortColumn(): string
    {
        return 'external_users.first_name';
    }

    /**
     * @return array<string, string>
     */
    protected function sortColumns(): array
    {
        $columns = [
            'first_name' => 'external_users.first_name',
            'last_name' => 'external_users.last_name',
            'email' => 'external_users.email',
        ];

        if ($this->role === 'athlete') {
            $columns['partner'] = 'selected_partner_name';
        }

        return $columns;
    }

    /**
     * @return array<string, array{label:string, sortable:bool, sort_field?:string, align?:string, width?:string, tooltip?:bool, truncate?:int, export_key?:string, formatter?:string}>
     */
    protected function columnDefinitions(): array
    {
        $columns = [
            'first_name' => ['label' => 'Vorname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Vorname'],
            'last_name' => ['label' => 'Nachname', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Nachname'],
            'email' => ['label' => 'E-Mail', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-56', 'export_key' => 'E-Mail', 'tooltip' => true, 'truncate' => 52],
            'phone_number' => ['label' => 'Telefon', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Telefon'],
            'city' => ['label' => 'Ort', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Ort'],
            'country_of_residence' => ['label' => 'Wohnsitzland', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Wohnsitzland'],
        ];

        if ($this->role === 'athlete') {
            $columns['public_id_string'] = ['label' => 'Öffentliche ID', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Öffentliche ID'];
            $columns['partner'] = ['label' => 'Benefizpartner:in', 'sortable' => true, 'align' => 'left', 'width' => 'min-w-48', 'export_key' => 'Benefizpartner:in'];
            $columns['group'] = ['label' => 'Gruppe', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Gruppe'];
            $columns['confirmed'] = ['label' => 'OK', 'sortable' => false, 'align' => 'center', 'width' => 'w-16 min-w-16', 'export_key' => 'OK'];
        }

        if ($this->role === 'donor') {
            $columns['invoice_status'] = ['label' => 'Rechnung', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-32', 'export_key' => 'Rechnung'];
            $columns['invoice_number'] = ['label' => 'Rechnungs-Nr.', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-28', 'export_key' => 'Rechnungs-Nr.'];
            $columns['invoice_total'] = ['label' => 'Rechnungsbetrag', 'sortable' => false, 'align' => 'right', 'width' => 'min-w-36', 'export_key' => 'Rechnungsbetrag'];
            $columns['invoice_remaining'] = ['label' => 'Offen', 'sortable' => false, 'align' => 'right', 'width' => 'min-w-32', 'export_key' => 'Offen'];
            $columns['invoice_sent_at'] = ['label' => 'Gesendet am', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Gesendet am'];
            $columns['invoice_reminder_sent_at'] = ['label' => 'Erinnerung am', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Erinnerung am'];
            $columns['invoice_synced_at'] = ['label' => 'Aktualisiert am', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-36', 'export_key' => 'Aktualisiert am'];
        }

        $columns['events'] = ['label' => 'Anlässe', 'sortable' => false, 'align' => 'left', 'width' => 'min-w-40', 'export_key' => 'Anlässe'];

        return $columns;
    }

    /**
     * @return array<int, string>
     */
    protected function defaultVisibleColumns(): array
    {
        $columns = [
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'city',
            'events',
        ];

        if ($this->role === 'athlete') {
            array_splice($columns, 5, 0, ['partner', 'group', 'confirmed']);
        } else {
            array_splice($columns, 5, 0, ['invoice_status', 'invoice_total', 'invoice_sent_at']);
        }

        return $columns;
    }

    /**
     * @return Collection<int, DonationEvent>
     */
    public function linkedEvents(ExternalUser $person): Collection
    {
        $events = $this->role === 'athlete'
            ? $person->athleteRegistrations->pluck('donationEvent')
            : $person->donationsAsDonor->pluck('athleteRegistration.donationEvent');

        return $events
            ->filter(fn (mixed $event): bool => $event instanceof DonationEvent)
            ->unique('id')
            ->sortByDesc('starts_at')
            ->values();
    }

    public function selectedAthletePartner(ExternalUser $person): string
    {
        $registration = $this->selectedAthleteRegistration($person);

        if (! $registration instanceof AthleteRegistration) {
            return '-';
        }

        return $registration->partner->name ?? __('app.equal_split_full');
    }

    public function selectedAthleteGroup(ExternalUser $person): string
    {
        $registration = $this->selectedAthleteRegistration($person);

        return $registration->eventGroup->name ?? '-';
    }

    public function selectedAthleteConfirmed(ExternalUser $person): ?bool
    {
        $registration = $this->selectedAthleteRegistration($person);

        return $registration->verified ?? null;
    }

    public function selectedAthleteRegistration(ExternalUser $person): ?AthleteRegistration
    {
        if ($this->role !== 'athlete' || $this->eventSlug === null || $this->eventSlug === '') {
            return null;
        }

        $registration = $person->athleteRegistrations->first(
            fn (AthleteRegistration $registration): bool => $registration->donationEvent->slug === $this->eventSlug,
        );

        return $registration instanceof AthleteRegistration ? $registration : null;
    }

    public function selectedEvent(): ?DonationEvent
    {
        if ($this->resolvedInvoiceEventChecked) {
            return $this->resolvedInvoiceEvent;
        }

        $this->resolvedInvoiceEventChecked = true;

        if ($this->eventSlug !== null && $this->eventSlug !== '') {
            $this->resolvedInvoiceEvent = DonationEvent::query()->where('slug', $this->eventSlug)->first();
        }

        return $this->resolvedInvoiceEvent;
    }

    public function invoiceActionsEnabled(): bool
    {
        return $this->role === 'donor' && $this->selectedEvent() instanceof DonationEvent;
    }

    public function donorInvoice(ExternalUser $person): ?DonorEventInvoice
    {
        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            return null;
        }

        $invoices = $person->relationLoaded('donorEventInvoices')
            ? $person->donorEventInvoices
            : $person->donorEventInvoices()->where('donation_event_id', $event->id)->get();

        return $invoices->first(fn (DonorEventInvoice $invoice): bool => $invoice->donation_event_id === $event->id);
    }

    public function invoiceStatus(ExternalUser $person): ?DonorInvoiceStatus
    {
        if (! $this->invoiceActionsEnabled()) {
            return null;
        }

        $invoice = $this->donorInvoice($person);

        return ! $invoice instanceof DonorEventInvoice ? DonorInvoiceStatus::NotCreated : $this->donorInvoices->status($invoice);
    }

    public function invoiceStatusColor(DonorInvoiceStatus $status): string
    {
        return match ($status) {
            DonorInvoiceStatus::RemoteDeleted, DonorInvoiceStatus::Unknown => 'red',
            DonorInvoiceStatus::Paid => 'green',
            DonorInvoiceStatus::Writeoff, DonorInvoiceStatus::Created, DonorInvoiceStatus::NotCreated => 'zinc',
            DonorInvoiceStatus::Overdue => 'orange',
            DonorInvoiceStatus::PartiallyPaid => 'amber',
            DonorInvoiceStatus::Sent => 'blue',
        };
    }

    public function invoiceNumber(ExternalUser $person): string
    {
        return $this->donorInvoice($person)->webling_invoice_number ?? '-';
    }

    public function invoiceTotal(ExternalUser $person): string
    {
        $invoice = $this->donorInvoice($person);
        $cents = $invoice->webling_total_cents ?? $invoice->source_total_cents ?? null;

        return $cents === null ? '-' : $this->formatMoney($cents / 100);
    }

    public function invoiceRemaining(ExternalUser $person): string
    {
        $cents = $this->donorInvoice($person)?->webling_remaining_cents;

        return $cents === null ? '-' : $this->formatMoney($cents / 100);
    }

    public function invoiceSentAt(ExternalUser $person): string
    {
        return $this->formatDateTime($this->donorInvoice($person)?->invoice_sent_at);
    }

    public function invoiceReminderSentAt(ExternalUser $person): string
    {
        return $this->formatDateTime($this->donorInvoice($person)?->invoice_reminder_sent_at);
    }

    public function invoiceSyncedAt(ExternalUser $person): string
    {
        return $this->formatDateTime($this->donorInvoice($person)?->webling_synced_at);
    }

    public function invoiceWeblingUrl(ExternalUser $person): ?string
    {
        $debitorId = $this->donorInvoice($person)?->webling_debitor_id;

        return $debitorId === null ? null : $this->weblingInvoices->debitorUrl($debitorId);
    }

    public function canCreateInvoiceForRow(ExternalUser $person): bool
    {
        $status = $this->invoiceStatus($person);

        return $status === DonorInvoiceStatus::NotCreated || $status === DonorInvoiceStatus::RemoteDeleted;
    }

    public function canDownloadInvoiceForRow(ExternalUser $person): bool
    {
        $invoice = $this->donorInvoice($person);

        return $invoice instanceof DonorEventInvoice
            && $invoice->remote_deleted_at === null
            && $this->hasInvoicePdf($invoice);
    }

    public function canSendInvoiceForRow(ExternalUser $person): bool
    {
        $invoice = $this->donorInvoice($person);
        $event = $this->selectedEvent();

        return $invoice instanceof DonorEventInvoice
            && $event instanceof DonationEvent
            && $this->canSendInvoice($invoice, $event, $person);
    }

    public function canRemindInvoiceForRow(ExternalUser $person): bool
    {
        $invoice = $this->donorInvoice($person);

        return $invoice instanceof DonorEventInvoice
            && $invoice->invoice_sent_at !== null
            && $invoice->remote_deleted_at === null
            && $invoice->webling_debitor_id !== null
            && $this->donorInvoices->status($invoice) !== DonorInvoiceStatus::Unknown;
    }

    public function canDeleteInvoiceForRow(ExternalUser $person): bool
    {
        $invoice = $this->donorInvoice($person);

        return $invoice instanceof DonorEventInvoice
            && $invoice->remote_deleted_at === null
            && ! in_array($this->donorInvoices->status($invoice), [DonorInvoiceStatus::Paid, DonorInvoiceStatus::Writeoff, DonorInvoiceStatus::PartiallyPaid, DonorInvoiceStatus::Unknown], true);
    }

    public function confirmCreateInvoice(int $externalUserId): void
    {
        $event = $this->requireInvoiceEvent();

        if (! $event instanceof DonationEvent) {
            return;
        }

        // Warn and require explicit confirmation before event end.
        if (! $event->hasEnded()) {
            $this->openInvoiceConfirm('create', $externalUserId);

            return;
        }

        $this->createInvoiceForEventDonor($externalUserId, $event);
    }

    public function sendInvoice(int $externalUserId): void
    {
        $invoice = $this->requireInvoiceForUser($externalUserId);

        if (! $invoice instanceof DonorEventInvoice) {
            return;
        }

        if ($invoice->invoice_sent_at !== null) {
            $this->openInvoiceConfirm('send', $externalUserId);

            return;
        }

        $this->runSendInvoice($invoice->externalUser);
    }

    public function sendInvoiceReminder(int $externalUserId): void
    {
        $invoice = $this->requireInvoiceForUser($externalUserId);

        if (! $invoice instanceof DonorEventInvoice) {
            return;
        }

        if ($invoice->invoice_reminder_sent_at !== null) {
            $this->openInvoiceConfirm('reminder', $externalUserId);

            return;
        }

        $this->runReminderInvoice($invoice->externalUser);
    }

    public function confirmDeleteInvoice(int $externalUserId): void
    {
        if (! $this->requireInvoiceForUser($externalUserId) instanceof DonorEventInvoice) {
            return;
        }

        $this->openInvoiceConfirm('delete', $externalUserId);
    }

    public function downloadInvoicePdf(int $externalUserId): ?HttpResponse
    {
        $this->ensureAuthenticated();

        $event = $this->requireInvoiceEvent();

        if (! $event instanceof DonationEvent) {
            return null;
        }

        $invoice = $this->eventInvoice(ExternalUser::query()->findOrFail($externalUserId), $event);
        $payload = ! $invoice instanceof DonorEventInvoice ? null : ($this->downloadDonorInvoicePdf)($invoice);

        if ($payload === null) {
            Flux::toast(
                heading: 'Kein PDF gefunden',
                text: 'Für diese Rechnung ist kein PDF verfügbar.',
                variant: 'warning',
            );

            return null;
        }

        return response()->download($payload['absolute_path'], $payload['file_name'], ['Content-Type' => 'application/pdf']);
    }

    public function confirmBulkCreateInvoices(): void
    {
        $event = $this->requireInvoiceSelection();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $rows = $this->selectedEventInvoiceRows($event);

        foreach ($this->selectedIds() as $userId) {
            $row = $rows->get($userId);
            if ($row === null || $row->remote_deleted_at !== null || $row->webling_debitor_id === null || $row->pdf_path === null) {
                $this->bulkEligibleCount++;
            } else {
                $this->bulkSkippedCount++;
            }
        }

        if ($this->bulkEligibleCount === 0) {
            Flux::toast(
                heading: 'Nichts zu tun',
                text: 'Alle ausgewählten Spender:innen haben bereits eine Rechnung.',
                variant: 'info',
            );

            return;
        }

        $this->openInvoiceConfirm('bulk_create', null);
    }

    public function confirmBulkSendInvoices(): void
    {
        $event = $this->requireInvoiceSelection();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $eligible = $this->selectedEventInvoices($event)->filter(
            fn (DonorEventInvoice $invoice): bool => $invoice->invoice_sent_at === null && $this->canSendInvoice($invoice, $event, $invoice->externalUser),
        );

        $this->bulkEligibleCount = $eligible->count();
        $this->bulkSkippedCount = $this->selectedCount() - $this->bulkEligibleCount;

        if ($this->bulkEligibleCount === 0) {
            Flux::toast(
                heading: 'Rechnungen nicht versendet',
                text: ! $event->hasEnded()
                    ? 'Der Anlass ist noch nicht beendet. Rechnungen können erst danach versendet werden.'
                    : 'Keine der ausgewählten Rechnungen kann gesendet werden.',
                variant: 'info',
            );

            return;
        }

        $this->openInvoiceConfirm('bulk_send', null);
    }

    public function confirmBulkSendInvoiceReminders(): void
    {
        $event = $this->requireInvoiceSelection();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $eligible = $this->selectedEventInvoices($event)->filter(
            fn (DonorEventInvoice $invoice): bool => $invoice->invoice_sent_at !== null
                && $invoice->invoice_reminder_sent_at === null
                && $invoice->remote_deleted_at === null
                && $invoice->webling_debitor_id !== null,
        );

        $this->bulkEligibleCount = $eligible->count();
        $this->bulkSkippedCount = $this->selectedCount() - $this->bulkEligibleCount;

        if ($this->bulkEligibleCount === 0) {
            Flux::toast(
                heading: 'Nichts zu senden',
                text: 'Keine der ausgewählten Rechnungen kann gemahnt werden.',
                variant: 'info',
            );

            return;
        }

        $this->openInvoiceConfirm('bulk_reminder', null);
    }

    public function downloadSelectedInvoiceArchive(): ?HttpResponse
    {
        $this->ensureAuthenticated();

        $event = $this->requireInvoiceEvent();

        if (! $event instanceof DonationEvent) {
            return null;
        }

        $invoiceIds = $this->selectedEventInvoices($event)->pluck('id')->all();

        if ($invoiceIds === []) {
            Flux::toast(
                heading: 'Keine Rechnungen',
                text: 'Für die ausgewählten Spender:innen existieren keine Rechnungen.',
                variant: 'warning',
            );

            return null;
        }

        try {
            $result = ($this->downloadEventInvoiceArchive)($event, $invoiceIds);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            Flux::toast(
                heading: 'Download nicht möglich',
                text: $invalidArgumentException->getMessage(),
                variant: 'warning',
            );

            return null;
        }

        if ($result['skipped_invoice_ids'] !== []) {
            Flux::toast(
                heading: 'Rechnungen heruntergeladen',
                text: count($result['skipped_invoice_ids']).' Rechnung(en) ohne verfügbares PDF wurden übersprungen.',
                variant: 'info',
            );
        }

        return $result['response'];
    }

    public function refreshInvoiceStatuses(): void
    {
        $this->ensureAuthenticated();

        $event = $this->requireInvoiceEvent();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $invoices = DonorEventInvoice::query()
            ->where('donation_event_id', $event->id)
            ->whereNotNull('webling_debitor_id')
            ->whereNull('remote_deleted_at')
            ->whereHas('externalUser')
            ->get();

        if ($invoices->isEmpty()) {
            Flux::toast(
                heading: 'Nichts zu aktualisieren',
                text: 'Für diesen Anlass existieren keine erstellten Rechnungen.',
                variant: 'info',
            );

            return;
        }

        $result = ($this->runDonorInvoiceBulk)(
            $event,
            $invoices,
            fn (DonorEventInvoice $invoice) => ($this->refreshDonorInvoiceStatus)($invoice),
        );

        Flux::toast(
            heading: 'Rechnungsstatus aktualisiert',
            text: $this->bulkResultText($result),
            variant: $result['failed'] > 0 ? 'warning' : 'success',
        );
    }

    public function paymentStatusSummary(): void
    {
        $this->ensureAuthenticated();

        $event = $this->requireInvoiceEvent();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $this->dispatch('showPaymentStatusSummary', summary: $this->invoiceSummaryCounts($event));
    }

    public function runConfirmedInvoiceAction(): void
    {
        $this->ensureAuthenticated();

        $action = $this->confirmingInvoiceAction;
        $userId = $this->confirmingInvoiceUserId;
        $this->cancelInvoiceConfirm();

        if ($action === null) {
            return;
        }

        $event = $this->requireInvoiceEvent();

        if (! $event instanceof DonationEvent) {
            return;
        }

        match ($action) {
            'create' => $this->createInvoiceForEventDonor((int) $userId, $event),
            'send' => $this->runSendInvoice($this->eventInvoicePerson((int) $userId)),
            'reminder' => $this->runReminderInvoice($this->eventInvoicePerson((int) $userId)),
            'delete' => $this->runDeleteInvoice($this->eventInvoicePerson((int) $userId)),
            'bulk_create' => $this->runBulkCreateInvoices($event),
            'bulk_send' => $this->runBulkSendInvoices($event),
            'bulk_reminder' => $this->runBulkSendInvoiceReminders($event),
            default => null,
        };
    }

    public function cancelInvoiceConfirm(): void
    {
        $this->confirmingInvoiceAction = null;
        $this->confirmingInvoiceUserId = null;
        $this->bulkEligibleCount = 0;
        $this->bulkSkippedCount = 0;

        Flux::modal('admin-person-invoice-confirm')->close();
    }

    public function invoiceConfirmHeading(): string
    {
        return match ($this->confirmingInvoiceAction) {
            'create' => 'Rechnung jetzt erstellen?',
            'send' => 'Rechnung erneut senden?',
            'reminder' => 'Zahlungserinnerung erneut senden?',
            'delete' => 'Rechnung löschen?',
            'bulk_create' => 'Rechnungen erstellen?',
            'bulk_send' => 'Rechnungen senden?',
            'bulk_reminder' => 'Zahlungserinnerungen senden?',
            default => 'Bestätigen',
        };
    }

    public function invoiceConfirmText(): string
    {
        if (in_array($this->confirmingInvoiceAction, ['bulk_create', 'bulk_send', 'bulk_reminder'], true)) {
            $verb = match ($this->confirmingInvoiceAction) {
                'bulk_create' => 'erstellt',
                'bulk_send' => 'gesendet',
                default => 'gemahnt',
            };

            $text = $this->bulkEligibleCount.' Rechnung(en) werden '.$verb.', '.$this->bulkSkippedCount.' werden übersprungen.';

            return $this->confirmingInvoiceAction === 'bulk_create' && ! $this->selectedEvent()?->hasEnded()
                ? 'Der Anlass ist noch nicht beendet. '.$text
                : $text;
        }

        $name = $this->confirmingInvoicePerson()->privacy_name ?? 'diese:r Spender:in';

        return match ($this->confirmingInvoiceAction) {
            'create' => 'Der Anlass ist noch nicht beendet. Möchtest du die Rechnung für '.$name.' trotzdem jetzt erstellen?',
            'send' => 'Die Rechnung für '.$name.' wurde bereits versendet. Möchtest du sie erneut senden?',
            'reminder' => 'Die Zahlungserinnerung für '.$name.' wurde bereits versendet. Möchtest du sie erneut senden?',
            'delete' => 'Die Rechnung für '.$name.' wird in Webling gelöscht und die lokal gespeicherte PDF entfernt. Dies kann nicht rückgängig gemacht werden.',
            default => '',
        };
    }

    public function invoiceConfirmButtonLabel(): string
    {
        return match ($this->confirmingInvoiceAction) {
            'create' => 'Jetzt erstellen',
            'send', 'reminder' => 'Erneut senden',
            'delete' => 'Endgültig löschen',
            'bulk_create' => 'Erstellen',
            'bulk_send' => 'Senden',
            'bulk_reminder' => 'Mahnen',
            default => 'Bestätigen',
        };
    }

    public function confirmingInvoiceIsDestructive(): bool
    {
        return $this->confirmingInvoiceAction === 'delete';
    }

    /**
     * @return array<string, int>
     */
    protected function invoiceSummaryCounts(DonationEvent $event): array
    {
        $counts = collect(DonorInvoiceStatus::cases())
            ->mapWithKeys(fn (DonorInvoiceStatus $status): array => [$status->value => 0])
            ->all();

        $rows = DonorEventInvoice::query()
            ->where('donation_event_id', $event->id)
            ->whereHas('externalUser')
            ->with('donationEvent')
            ->get();

        foreach ($rows as $row) {
            $counts[$this->donorInvoices->status($row)->value]++;
        }

        $donorIds = $this->donorService->forEvent($event)->pluck('id');
        $missing = $donorIds->diff($rows->pluck('external_user_id')->unique())->count();
        $counts['not_created'] += $missing;

        return $counts;
    }

    /**
     * @param  array{successful:int,skipped:int,failed:int,messages:list<string>}  $result
     */
    protected function bulkResultText(array $result): string
    {
        $parts = [$result['successful'].' erledigt'];

        if ($result['skipped'] > 0) {
            $parts[] = $result['skipped'].' übersprungen';
        }

        if ($result['failed'] > 0) {
            $parts[] = $result['failed'].' fehlgeschlagen';
        }

        return implode(', ', $parts).'.';
    }

    protected function requireInvoiceEvent(): ?DonationEvent
    {
        $this->ensureAuthenticated();

        $event = $this->selectedEvent();

        if ($event instanceof DonationEvent) {
            return $event;
        }

        Flux::toast(
            heading: 'Anlass auswählen',
            text: 'Rechnungen können nur für einen ausgewählten Anlass verwaltet werden.',
            variant: 'warning',
        );

        return null;
    }

    protected function requireInvoiceSelection(): ?DonationEvent
    {
        if ($this->selectedIds() === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine:n Spender:in aus.');

            return null;
        }

        return $this->requireInvoiceEvent();
    }

    protected function requireInvoiceForUser(int $externalUserId): ?DonorEventInvoice
    {
        $event = $this->requireInvoiceEvent();

        if (! $event instanceof DonationEvent) {
            return null;
        }

        $invoice = $this->eventInvoice(ExternalUser::query()->findOrFail($externalUserId), $event);

        if (! $invoice instanceof DonorEventInvoice) {
            Flux::toast(
                heading: 'Keine Rechnung',
                text: 'Für diese Person existiert keine Rechnung im ausgewählten Anlass.',
                variant: 'warning',
            );
        }

        return $invoice;
    }

    protected function eventInvoicePerson(int $externalUserId): ExternalUser
    {
        return ExternalUser::query()->findOrFail($externalUserId);
    }

    protected function hasInvoicePdf(DonorEventInvoice $invoice): bool
    {
        return $invoice->pdf_disk !== null
            && $invoice->pdf_path !== null
            && Storage::disk($invoice->pdf_disk)->exists($invoice->pdf_path);
    }

    protected function canSendInvoice(DonorEventInvoice $invoice, DonationEvent $event, ExternalUser $person): bool
    {
        return $event->hasEnded()
            && $invoice->remote_deleted_at === null
            && $invoice->webling_debitor_id !== null
            && trim($person->email) !== ''
            && $this->hasInvoicePdf($invoice)
            && ! in_array($this->donorInvoices->status($invoice), [DonorInvoiceStatus::Paid, DonorInvoiceStatus::Writeoff, DonorInvoiceStatus::Unknown], true);
    }

    protected function createInvoiceForEventDonor(int $externalUserId, DonationEvent $event): void
    {
        $person = $this->donorService->forEvent($event)->find($externalUserId);

        if (! $person instanceof ExternalUser) {
            Flux::toast(
                heading: 'Keine Spende',
                text: 'Diese Person hat im ausgewählten Anlass keine Spende hinterlegt.',
                variant: 'warning',
            );

            return;
        }

        $this->runCreateInvoice($person, $event);
    }

    protected function eventInvoice(ExternalUser $person, DonationEvent $event): ?DonorEventInvoice
    {
        return DonorEventInvoice::query()
            ->where('external_user_id', $person->id)
            ->where('donation_event_id', $event->id)
            ->first();
    }

    /**
     * @return Collection<int, DonorEventInvoice>
     */
    protected function selectedEventInvoices(DonationEvent $event): Collection
    {
        return $this->selectedEventInvoiceQuery($event)
            ->with(['externalUser', 'donationEvent'])
            ->get();
    }

    /**
     * @return Collection<int, DonorEventInvoice>
     */
    protected function selectedEventInvoiceRows(DonationEvent $event): Collection
    {
        return $this->selectedEventInvoiceQuery($event)
            ->get()
            ->keyBy('external_user_id');
    }

    /** @return Builder<DonorEventInvoice> */
    protected function selectedEventInvoiceQuery(DonationEvent $event): Builder
    {
        return DonorEventInvoice::query()
            ->where('donation_event_id', $event->id)
            ->whereIn('external_user_id', $this->selectedIds())
            ->whereHas('externalUser');
    }

    protected function openInvoiceConfirm(string $action, ?int $externalUserId): void
    {
        $this->confirmingInvoiceAction = $action;
        $this->confirmingInvoiceUserId = $externalUserId;

        Flux::modal('admin-person-invoice-confirm')->show();
    }

    protected function confirmingInvoicePerson(): ?ExternalUser
    {
        return $this->confirmingInvoiceUserId === null
            ? null
            : ExternalUser::query()->find($this->confirmingInvoiceUserId);
    }

    protected function runCreateInvoice(ExternalUser $person, DonationEvent $event): void
    {
        try {
            ($this->createDonorInvoice)($person, $event);

            Flux::toast(
                heading: 'Rechnung in Erstellung',
                text: 'Die Rechnung für '.$person->privacy_name.' wird erstellt.',
                variant: 'success',
            );
        } catch (DonorInvoiceGuardException $guardException) {
            Flux::toast(
                heading: 'Rechnung nicht erstellt',
                text: $guardException->getMessage(),
                variant: 'warning',
            );
        } catch (\Throwable $throwable) {
            report($throwable);

            Flux::toast(
                heading: 'Rechnung nicht erstellt',
                text: 'Die Rechnung für '.$person->privacy_name.' konnte nicht erstellt werden.',
                variant: 'danger',
            );
        }
    }

    protected function runSendInvoice(ExternalUser $person): void
    {
        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $invoice = $this->eventInvoice($person, $event);

        if (! $invoice instanceof DonorEventInvoice) {
            Flux::toast(
                heading: 'Keine Rechnung',
                text: 'Für diese Person existiert keine Rechnung im ausgewählten Anlass.',
                variant: 'warning',
            );

            return;
        }

        try {
            ($this->sendDonorInvoice)($invoice);

            Flux::toast(
                heading: 'Rechnung zum Versand eingeplant',
                text: 'Die Rechnung wird an '.$person->email.' gesendet.',
                variant: 'success',
            );
        } catch (DonorInvoiceGuardException $guardException) {
            Flux::toast(
                heading: 'Rechnung nicht versendet',
                text: $guardException->getMessage(),
                variant: 'warning',
            );
        } catch (\Throwable $throwable) {
            report($throwable);

            Flux::toast(
                heading: 'Rechnung nicht versendet',
                text: 'Die Rechnung konnte nicht gesendet werden.',
                variant: 'danger',
            );
        }
    }

    protected function runReminderInvoice(ExternalUser $person): void
    {
        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $invoice = $this->eventInvoice($person, $event);

        if (! $invoice instanceof DonorEventInvoice) {
            Flux::toast(
                heading: 'Keine Rechnung',
                text: 'Für diese Person existiert keine Rechnung im ausgewählten Anlass.',
                variant: 'warning',
            );

            return;
        }

        try {
            ($this->sendDonorInvoiceReminder)($invoice);

            Flux::toast(
                heading: 'Zahlungserinnerung zum Versand eingeplant',
                text: 'Die Zahlungserinnerung wird an '.$person->email.' gesendet.',
                variant: 'success',
            );
        } catch (DonorInvoiceGuardException $guardException) {
            Flux::toast(
                heading: 'Zahlungserinnerung nicht versendet',
                text: $guardException->getMessage(),
                variant: 'warning',
            );
        } catch (\Throwable $throwable) {
            report($throwable);

            Flux::toast(
                heading: 'Zahlungserinnerung nicht versendet',
                text: 'Die Zahlungserinnerung konnte nicht gesendet werden.',
                variant: 'danger',
            );
        }
    }

    protected function runDeleteInvoice(ExternalUser $person): void
    {
        $event = $this->selectedEvent();

        if (! $event instanceof DonationEvent) {
            return;
        }

        $invoice = $this->eventInvoice($person, $event);

        if (! $invoice instanceof DonorEventInvoice) {
            Flux::toast(
                heading: 'Keine Rechnung',
                text: 'Für diese Person existiert keine Rechnung im ausgewählten Anlass.',
                variant: 'warning',
            );

            return;
        }

        try {
            ($this->deleteDonorInvoice)($invoice);

            Flux::toast(
                heading: 'Rechnung gelöscht',
                text: 'Die Rechnung für '.$person->privacy_name.' wurde gelöscht.',
                variant: 'success',
            );
        } catch (DonorInvoiceGuardException $guardException) {
            Flux::toast(
                heading: 'Rechnung nicht gelöscht',
                text: $guardException->getMessage(),
                variant: 'warning',
            );
        } catch (\Throwable $throwable) {
            report($throwable);

            Flux::toast(
                heading: 'Rechnung nicht gelöscht',
                text: 'Die Rechnung konnte nicht gelöscht werden.',
                variant: 'danger',
            );
        }
    }

    protected function runBulkCreateInvoices(DonationEvent $event): void
    {
        $eventDonorIds = $this->donorService->forEvent($event)->pluck('id')->all();
        $selectedIds = array_values(array_intersect($this->selectedIds(), $eventDonorIds));
        $rows = DonorEventInvoice::query()
            ->where('donation_event_id', $event->id)
            ->whereIn('external_user_id', $selectedIds)
            ->whereHas('externalUser')
            ->get()
            ->keyBy('external_user_id');

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach (ExternalUser::query()->whereIn('id', $selectedIds)->orderBy('id')->get() as $person) {
            $row = $rows->get($person->id);

            if ($row !== null && $row->remote_deleted_at === null && $row->webling_debitor_id !== null && $row->pdf_path !== null) {
                $skipped++;

                continue;
            }

            try {
                ($this->createDonorInvoice)($person, $event);
                $created++;
            } catch (DonorInvoiceGuardException) {
                $skipped++;
            } catch (\Throwable $throwable) {
                report($throwable);
                $failed++;
            }
        }

        $this->clearSelection();

        Flux::toast(
            heading: 'Rechnungen in Erstellung',
            text: $created.' in Auftrag gegeben, '.$skipped.' übersprungen'
                .($failed > 0 ? ', '.$failed.' fehlgeschlagen' : '').'.',
            variant: $failed > 0 ? 'warning' : 'success',
        );
    }

    protected function runBulkSendInvoices(DonationEvent $event): void
    {
        $allRows = $this->selectedEventInvoices($event);
        $withoutRow = $this->selectedCount() - $allRows->count();
        $invoices = $allRows->filter(fn (DonorEventInvoice $invoice): bool => $invoice->invoice_sent_at === null && $this->canSendInvoice($invoice, $event, $invoice->externalUser));
        $result = ($this->runDonorInvoiceBulk)(
            $event,
            $invoices,
            fn (DonorEventInvoice $invoice) => ($this->sendDonorInvoice)($invoice),
        );

        $result['skipped'] += $withoutRow + ($allRows->count() - $invoices->count());
        $this->clearSelection();

        Flux::toast(
            heading: 'Rechnungen gesendet',
            text: $this->bulkResultText($result),
            variant: $result['failed'] > 0 ? 'warning' : 'success',
        );
    }

    protected function runBulkSendInvoiceReminders(DonationEvent $event): void
    {
        $allRows = $this->selectedEventInvoices($event);
        $withoutRow = $this->selectedCount() - $allRows->count();
        $invoices = $allRows->filter(fn (DonorEventInvoice $invoice): bool => $invoice->invoice_reminder_sent_at === null);
        $result = ($this->runDonorInvoiceBulk)(
            $event,
            $invoices,
            fn (DonorEventInvoice $invoice) => ($this->sendDonorInvoiceReminder)($invoice),
        );

        $result['skipped'] += $withoutRow + ($allRows->count() - $invoices->count());
        $this->clearSelection();

        Flux::toast(
            heading: 'Zahlungserinnerungen gesendet',
            text: $this->bulkResultText($result),
            variant: $result['failed'] > 0 ? 'warning' : 'success',
        );
    }

    public function roleLabel(): string
    {
        return $this->role === 'athlete' ? 'Sportler:innen' : 'Spender:innen';
    }

    protected function tableViewData(LengthAwarePaginator $paginator): array
    {
        return [
            'events' => DonationEvent::query()->latest('starts_at')->get(['id', 'title', 'slug', 'is_published']),
        ];
    }

    public function exportAll(string $format): ?HttpResponse
    {
        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, $this->exportPrefix().'_gesamt', $format);
    }

    public function exportSelected(string $format): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Zeile aus.');

            return null;
        }

        $rows = [];

        foreach ($this->queryForTable(ignoreSearch: true)->whereKey($selectedIds)->orderBy('id')->get() as $row) {
            $rows[] = $this->exportRow($row);
        }

        return $this->exportRowsToDownload($rows, $this->exportPrefix().'_auswahl', $format);
    }

    public function documentDownloadsEnabled(): bool
    {
        return $this->role === 'athlete' && $this->eventSlug !== null && $this->eventSlug !== '';
    }

    public function downloadAthleteDocument(int $externalUserId, string $type): ?HttpResponse
    {
        $event = $this->documentEvent();
        $documentType = $this->documentType($type);

        if (! $event instanceof DonationEvent || ! $documentType instanceof AthleteDocumentType) {
            return null;
        }

        try {
            return $this->withDocumentDownloadLock(fn (): HttpResponse => ($this->downloadAthleteDocumentAction)($event, $externalUserId, $documentType));
        } catch (ModelNotFoundException) {
            $this->toastDocumentError('Die Sportler:in wurde im ausgewählten Anlass nicht gefunden.');

            return null;
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->toastDocumentError($invalidArgumentException->getMessage());

            return null;
        }
    }

    public function downloadAllAthleteDocuments(string $type): ?HttpResponse
    {
        return $this->downloadAthleteDocumentArchive($type);
    }

    public function downloadSelectedAthleteDocuments(string $type): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Sportler:in aus.');

            return null;
        }

        return $this->downloadAthleteDocumentArchive($type, $selectedIds);
    }

    public function downloadAllAthleteStoryImages(): ?HttpResponse
    {
        return $this->downloadAthleteStoryImageArchive();
    }

    public function downloadSelectedAthleteStoryImages(): ?HttpResponse
    {
        $selectedIds = $this->selectedIds();

        if ($selectedIds === []) {
            $this->toastNoSelection('Bitte wähle mindestens eine Sportler:in aus.');

            return null;
        }

        return $this->downloadAthleteStoryImageArchive($selectedIds);
    }

    /**
     * @param  array<int, int>|null  $externalUserIds
     */
    protected function downloadAthleteDocumentArchive(string $type, ?array $externalUserIds = null): ?HttpResponse
    {
        $event = $this->documentEvent();
        $documentType = $this->documentType($type);

        if (! $event instanceof DonationEvent || ! $documentType instanceof AthleteDocumentType) {
            return null;
        }

        try {
            return $this->withDocumentDownloadLock(fn (): HttpResponse => ($this->downloadAthleteDocumentArchiveAction)($event, $documentType, $externalUserIds));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->toastDocumentError($invalidArgumentException->getMessage());

            return null;
        }
    }

    /**
     * @param  array<int, int>|null  $externalUserIds
     */
    protected function downloadAthleteStoryImageArchive(?array $externalUserIds = null): ?HttpResponse
    {
        $event = $this->documentEvent();

        if (! $event instanceof DonationEvent) {
            return null;
        }

        try {
            return $this->withDocumentDownloadLock(fn (): HttpResponse => ($this->downloadAthleteStoryImageArchiveAction)($event, $externalUserIds));
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->toastDocumentError($invalidArgumentException->getMessage());

            return null;
        }
    }

    /**
     * @param  Closure():HttpResponse  $download
     */
    protected function withDocumentDownloadLock(Closure $download): ?HttpResponse
    {
        $lock = Cache::lock('admin-athlete-document-download:'.Auth::id(), 600);

        if (! $lock->get()) {
            Flux::toast(
                heading: 'Dokumente werden bereits erstellt',
                text: 'Bitte warte, bis der aktuelle Download abgeschlossen ist.',
                variant: 'warning',
            );

            return null;
        }

        try {
            return $download();
        } finally {
            $lock->release();
        }
    }

    protected function documentEvent(): ?DonationEvent
    {
        $this->ensureAuthenticated();

        if (! $this->documentDownloadsEnabled()) {
            Flux::toast(
                heading: 'Anlass auswählen',
                text: 'Dokumente können nur für einen ausgewählten Anlass erstellt werden.',
                variant: 'warning',
            );

            return null;
        }

        $event = DonationEvent::query()->where('slug', $this->eventSlug)->first();

        if ($event instanceof DonationEvent) {
            return $event;
        }

        Flux::toast(
            heading: 'Anlass nicht gefunden',
            text: 'Der ausgewählte Anlass ist nicht mehr verfügbar.',
            variant: 'danger',
        );

        return null;
    }

    protected function documentType(string $type): ?AthleteDocumentType
    {
        $documentType = AthleteDocumentType::tryFrom($type);

        if ($documentType instanceof AthleteDocumentType) {
            return $documentType;
        }

        Flux::toast(
            heading: 'Ungültiges Dokument',
            text: 'Dieser Dokumenttyp ist nicht verfügbar.',
            variant: 'danger',
        );

        return null;
    }

    protected function toastDocumentError(string $text): void
    {
        Flux::toast(heading: 'Dokumente nicht erstellt', text: $text, variant: 'danger');
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function exportRow(mixed $row): array
    {
        $export = [
            'Vorname' => data_get($row, 'first_name'),
            'Nachname' => data_get($row, 'last_name'),
            'E-Mail' => data_get($row, 'email'),
            'Telefon' => data_get($row, 'phone_number'),
            'Ort' => data_get($row, 'city'),
            'Wohnsitzland' => data_get($row, 'country_of_residence'),
            'Anlässe' => $row instanceof ExternalUser ? $this->linkedEvents($row)->pluck('slug')->implode(', ') : '',
        ];

        if ($this->role === 'athlete' && $row instanceof ExternalUser) {
            $export['Öffentliche ID'] = data_get($row, 'public_id_string');
            $export['Benefizpartner:in'] = $this->selectedAthletePartner($row);
            $export['Gruppe'] = $this->selectedAthleteGroup($row);

            $confirmed = $this->selectedAthleteConfirmed($row);
            $export['OK'] = $confirmed === null ? '-' : ($confirmed ? 'OK' : 'NOK');
        }

        if ($this->role === 'donor' && $row instanceof ExternalUser) {
            $export['Rechnung'] = $this->invoiceStatus($row)?->label() ?? '';
            $export['Rechnungs-Nr.'] = $this->invoiceNumber($row);
            $export['Rechnungsbetrag'] = $this->invoiceTotal($row);
            $export['Offen'] = $this->invoiceRemaining($row);
            $export['Gesendet am'] = $this->invoiceSentAt($row);
            $export['Erinnerung am'] = $this->invoiceReminderSentAt($row);
            $export['Aktualisiert am'] = $this->invoiceSyncedAt($row);
        }

        return $export;
    }

    protected function exportPrefix(): string
    {
        return $this->role === 'athlete' ? 'sportler-innen' : 'spender-innen';
    }
}
