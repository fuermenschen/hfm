<?php

declare(strict_types=1);

namespace App\Support\Datatable\Actions;

class DonorBulkActionFactory
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function make(): array
    {
        $actions = [
            new DatatableActionDefinition(
                key: 'bulk-create',
                group: 'bulk',
                label: 'Rechnungen erstellen',
                execute: static fn (array $context): array => [
                    'type' => 'wire',
                    'click' => 'bulkCreateInvoice',
                    'loading_label' => 'Erstelle Rechnungen...',
                ],
            ),
            new DatatableActionDefinition(
                key: 'bulk-download',
                group: 'bulk',
                label: 'Rechnungen herunterladen',
                execute: static fn (array $context): array => [
                    'type' => 'wire',
                    'click' => 'bulkDownloadInvoice',
                    'loading_label' => 'Bereite ZIP vor...',
                ],
            ),
            new DatatableActionDefinition(
                key: 'bulk-send',
                group: 'bulk',
                label: 'Rechnungen senden',
                execute: static fn (array $context): array => [
                    'type' => 'wire',
                    'click' => 'bulkSendInvoice',
                    'loading_label' => 'Sende Rechnungen...',
                ],
            ),
            new DatatableActionDefinition(
                key: 'bulk-reminder',
                group: 'bulk',
                label: 'Erinnerungen senden',
                execute: static fn (array $context): array => [
                    'type' => 'wire',
                    'click' => 'bulkSendInvoiceReminder',
                    'loading_label' => 'Sende Erinnerungen...',
                ],
            ),
        ];

        $resolved = [];

        foreach ($actions as $action) {
            $item = $action->resolve();

            if ($item === null) {
                continue;
            }

            $resolved[] = $item;
        }

        return $resolved;
    }
}
