<?php

namespace App\Support\Datatable\Actions;

use App\Models\Donator;

class DonorRowActionFactory
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function make(Donator $donor): array
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
                icon: 'user',
                execute: static fn (array $payload): array => [
                    'type' => 'href',
                    'href' => route('show-donator', ['login_token' => $payload['donor']->login_token]),
                    'target' => '_blank',
                ],
            ),
            new DatatableActionDefinition(
                key: 'invoice-create',
                group: 'Rechnung',
                label: 'Rechnung erstellen',
                icon: 'document-plus',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'createDonorInvoice('.$payload['donor']->id.')'],
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_create'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-download',
                group: 'Rechnung',
                label: 'Rechnung herunterladen',
                icon: 'document-arrow-down',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'downloadDonorInvoice('.$payload['donor']->id.')'],
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_download'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-send',
                group: 'Rechnung',
                label: 'Rechnung senden',
                icon: 'paper-airplane',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'sendDonorInvoice('.$payload['donor']->id.')'],
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_send'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-send-reminder',
                group: 'Rechnung',
                label: 'Zahlungserinnerung senden',
                icon: 'bell-alert',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'sendDonorInvoiceReminder('.$payload['donor']->id.')'],
                visibleWhen: static fn (array $payload): bool => (bool) $payload['can_send_reminder'],
            ),
            new DatatableActionDefinition(
                key: 'invoice-show-webling',
                group: 'Rechnung',
                label: 'Rechnung in Webling anzeigen',
                icon: 'arrow-top-right-on-square',
                execute: static fn (array $payload): array => [
                    'type' => 'href',
                    'href' => (string) $payload['debitor_url'],
                    'target' => '_blank',
                ],
                visibleWhen: static fn (array $payload): bool => filled($payload['debitor_url']),
            ),
            new DatatableActionDefinition(
                key: 'invoice-delete',
                group: 'Rechnung',
                label: 'Rechnung löschen',
                icon: 'trash',
                variant: 'danger',
                execute: static fn (array $payload): array => ['type' => 'wire', 'click' => 'confirmDeleteDonorInvoice('.$payload['donor']->id.')'],
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
