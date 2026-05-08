<?php

declare(strict_types=1);

namespace App\Support\Datatable\Actions;

use App\Models\Donor;

class DonorRowActionFactory
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function make(Donor $donor): array
    {
        $hasPdf = filled(data_get($donor->webling_data, 'letter_pdf.path'));
        $hasDebitor = filled(data_get($donor->webling_data, 'debitor_id'));
        $debitorUrl = data_get($donor->webling_data, 'debitor_url');
        $paymentStatus = data_get($donor->webling_data, 'payment_status');

        $context = [
            'donor' => $donor,
            'has_pdf' => $hasPdf,
            'has_debitor' => $hasDebitor,
            'debitor_url' => is_string($debitorUrl) ? $debitorUrl : null,
            'payment_status' => is_string($paymentStatus) ? $paymentStatus : null,
            'can_download' => $hasPdf,
            'can_send' => $hasPdf && filled($donor->email),
            'can_create' => (! $hasDebitor) || (! $hasPdf),
            'can_delete' => $hasDebitor || $hasPdf,
            'can_send_reminder' => $hasPdf && filled($donor->email) && filled($donor->invoice_sent_at) && $paymentStatus === 'overdue',
        ];

        $actions = [
            new DatatableActionDefinition(
                key: 'donor-login',
                group: 'Spender:in',
                label: 'Als Spender einloggen',
                execute: static fn (array $payload): array => [
                    'type' => 'href',
                    'href' => route('show-donor', ['login_token' => $payload['donor']->login_token]),
                    'target' => '_blank',
                ],
                icon: 'user',
            ),
            new DatatableActionDefinition(
                key: 'invoice-create',
                group: 'Rechnung',
                label: 'Rechnung erstellen',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'createDonorInvoice('.$payload['donor']->id.')'],
                icon: 'document-plus',
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_create'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-download',
                group: 'Rechnung',
                label: 'Rechnung herunterladen',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'downloadDonorInvoice('.$payload['donor']->id.')'],
                icon: 'document-arrow-down',
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_download'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-send',
                group: 'Rechnung',
                label: 'Rechnung senden',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'sendDonorInvoice('.$payload['donor']->id.')'],
                icon: 'paper-airplane',
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_send'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-send-reminder',
                group: 'Rechnung',
                label: 'Zahlungserinnerung senden',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'sendDonorInvoiceReminder('.$payload['donor']->id.')'],
                icon: 'bell-alert',
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_send_reminder'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-show-webling',
                group: 'Rechnung',
                label: 'Rechnung in Webling anzeigen',
                execute: static fn (array $payload): array => [
                    'type' => 'href',
                    'href' => (string) $payload['debitor_url'],
                    'target' => '_blank',
                ],
                icon: 'arrow-top-right-on-square',
                visibleWhen: static fn (array $payload): bool => filled($payload['debitor_url']),
            ),
            new DatatableActionDefinition(
                key: 'invoice-delete',
                group: 'Rechnung',
                label: 'Rechnung löschen',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'confirmDeleteDonorInvoice('.$payload['donor']->id.')'],
                icon: 'trash',
                variant: 'danger',
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_delete'],
            ),
        ];

        $resolved = [];

        foreach ($actions as $action) {
            $item = $action->resolve($context);

            if ($item === null) {
                continue;
            }

            $resolved[$action->group()][] = $item;
        }

        if (empty($resolved['Rechnung'])) {
            $resolved['Rechnung'][] = [
                'key' => 'invoice-none',
                'group' => 'Rechnung',
                'label' => 'Keine Aktionen verfügbar',
                'type' => 'static',
                'disabled' => true,
            ];
        }

        return $resolved;
    }
}
