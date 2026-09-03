<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        :placeholder="$this->roleLabel().' suchen...'"
                        icon="magnifying-glass"
                    />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-datatable.export-dropdown />
                        @if ($role === 'athlete')
                            <flux:dropdown>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="document-text"
                                    wire:loading.attr="disabled"
                                    wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
                                    :disabled="! $this->documentDownloadsEnabled()"
                                >
                                    <span
                                        wire:loading.remove
                                        wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
                                    >Dokumente</span>
                                    <span
                                        wire:loading
                                        wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
                                    >Wird erstellt...</span>
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.group heading="Willkommensbrief">
                                        <flux:menu.item
                                            wire:click="downloadAllAthleteDocuments('welcome-letter')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadAllAthleteDocuments"
                                            icon="document-text"
                                            :disabled="! $this->documentDownloadsEnabled()"
                                        >
                                            Alle Sportler:innen
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="downloadSelectedAthleteDocuments('welcome-letter')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadSelectedAthleteDocuments"
                                            icon="check-circle"
                                            :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
                                        >
                                            Ausgewählte Sportler:innen
                                        </flux:menu.item>
                                    </flux:menu.group>
                                    <flux:menu.group heading="Personalisierter Flyer">
                                        <flux:menu.item
                                            wire:click="downloadAllAthleteDocuments('personalized-flyer')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadAllAthleteDocuments"
                                            icon="document-text"
                                            :disabled="! $this->documentDownloadsEnabled()"
                                        >
                                            Alle Sportler:innen
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="downloadSelectedAthleteDocuments('personalized-flyer')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadSelectedAthleteDocuments"
                                            icon="check-circle"
                                            :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
                                        >
                                            Ausgewählte Sportler:innen
                                        </flux:menu.item>
                                    </flux:menu.group>
                                    <flux:menu.group heading="Story-Bilder">
                                        <flux:menu.item
                                            wire:click="downloadAllAthleteStoryImages"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadAllAthleteStoryImages"
                                            icon="photo"
                                            :disabled="! $this->documentDownloadsEnabled()"
                                        >
                                            Alle Sportler:innen
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="downloadSelectedAthleteStoryImages"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadSelectedAthleteStoryImages"
                                            icon="check-circle"
                                            :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
                                        >
                                            Ausgewählte Sportler:innen
                                        </flux:menu.item>
                                    </flux:menu.group>
                                </flux:menu>
                            </flux:dropdown>
                            @if (! $this->documentDownloadsEnabled())
                                <flux:callout icon="information-circle" variant="secondary" class="py-1.5">
                                    <flux:callout.text>
                                        Für Dokumente bitte genau einen Anlass auswählen.</flux:callout.text>
                                </flux:callout>
                            @endif
                            <flux:text
                                wire:loading.flex
                                wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments,downloadAllAthleteStoryImages,downloadSelectedAthleteStoryImages"
                                class="items-center gap-1 text-sm text-zinc-500"
                            >
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                                Dokumente werden erstellt...
                            </flux:text>
                        @endif
                        @if ($role === 'donor')
                            <flux:dropdown>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="document-text"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmBulkCreateInvoices,confirmBulkSendInvoices,confirmBulkSendInvoiceReminders,downloadSelectedInvoiceArchive,refreshInvoiceStatuses,paymentStatusSummary"
                                    :disabled="! $this->invoiceActionsEnabled()"
                                >
                                    Rechnungen
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.item
                                        wire:click="confirmBulkCreateInvoices"
                                        icon="document-plus"
                                        :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
                                    >
                                        Für Auswahl erstellen
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="confirmBulkSendInvoices"
                                        icon="paper-airplane"
                                        :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
                                    >
                                        Für Auswahl senden
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="confirmBulkSendInvoiceReminders"
                                        icon="bell-alert"
                                        :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
                                    >
                                        Zahlungserinnerung für Auswahl
                                    </flux:menu.item>
                                    <flux:menu.item
                                        wire:click="downloadSelectedInvoiceArchive"
                                        icon="document-arrow-down"
                                        :disabled="! $this->invoiceActionsEnabled() || $this->selectedCount() === 0"
                                    >
                                        PDFs herunterladen (ZIP)
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="arrow-path"
                                wire:click="refreshInvoiceStatuses"
                                wire:loading.attr="disabled"
                                wire:target="refreshInvoiceStatuses"
                                :disabled="! $this->invoiceActionsEnabled()"
                            >
                                Status aktualisieren
                            </flux:button>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="chart-bar"
                                wire:click="paymentStatusSummary"
                                wire:loading.attr="disabled"
                                wire:target="paymentStatusSummary"
                                :disabled="! $this->invoiceActionsEnabled()"
                            >
                                Zahlungsstatus
                            </flux:button>
                            @if (! $this->invoiceActionsEnabled())
                                <flux:callout icon="information-circle" variant="secondary" class="py-1.5">
                                    <flux:callout.text>
                                        Für Rechnungen bitte genau einen Anlass auswählen.</flux:callout.text>
                                </flux:callout>
                            @endif
                        @endif
                        <x-datatable.column-visibility-dropdown :column-options="$this->visibleColumnOptions()" />
                    </div>
                </x-slot:bottomLeft>

                <x-slot:bottomRight>
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:text class="text-sm text-zinc-500">{{ $external_users->total() }} {{ $this->roleLabel() }}</flux:text>
                        <x-datatable.event-filter :events="$events" />
                    </div>
                </x-slot:bottomRight>
            </x-datatable.toolbar-grid>
        </x-slot:toolbar>

        <flux:checkbox.group wire:model.live="checkboxValues">
            @php($visibleColumns = $this->visibleColumnDefinitions())
            <flux:table class="min-w-max">
                <flux:table.columns>
                    <flux:table.column>
                        <flux:field variant="inline">
                            <flux:checkbox.all />
                        </flux:field>
                    </flux:table.column>
                    @foreach ($visibleColumns as $columnKey => $columnDefinition)
                        @php($headerAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                        @php($headerClass = trim(($columnDefinition['width'] ?? '').' '.$headerAlignClass))
                        <flux:table.column class="{{ $headerClass }}">
                            @if ($columnDefinition['sortable'])
                                @include('components.datatable.sortable-header', ['column' => $columnKey, 'label' => $columnDefinition['label']])
                            @else
                                <span>{{ $columnDefinition['label'] }}</span>
                            @endif
                        </flux:table.column>
                    @endforeach
                    @if ($role === 'athlete')
                        <flux:table.column class="w-28 text-right">Dokumente</flux:table.column>
                    @endif
                    <flux:table.column class="w-1 text-right whitespace-nowrap">Aktion</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    <flux:table.row wire:loading.delay.short wire:target="{{ $this->tableLoadingTargets() }}">
                        <flux:table.cell colspan="99" class="text-center">
                            <div class="flex items-center justify-center gap-2 py-4 text-sm">
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                                Tabelle wird aktualisiert...
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                    @forelse ($external_users as $row)
                        @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                        <flux:table.row
                            wire:key="row-{{ $row->id }}"
                            class="{{ $rowClass }}"
                            wire:loading.remove
                            wire:target="{{ $this->tableLoadingTargets() }}"
                        >
                            <flux:table.cell>
                                <flux:field variant="inline">
                                    <flux:checkbox value="{{ $row->id }}" />
                                </flux:field>
                            </flux:table.cell>
                            @foreach ($visibleColumns as $columnKey => $columnDefinition)
                                @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                                @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                                <flux:table.cell class="{{ $cellClass }}">
                                    @if ($columnKey === 'events')
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($this->linkedEvents($row) as $event)
                                                <flux:badge size="sm" color="zinc">{{ $event->slug }}</flux:badge>
                                            @endforeach
                                        </div>
                                    @elseif ($columnKey === 'invoice_status')
                                        @php($invoiceStatus = $this->invoiceStatus($row))
                                        @if ($invoiceStatus !== null)
                                            <flux:badge size="sm" :color="$this->invoiceStatusColor($invoiceStatus)">
                                                {{ $invoiceStatus->label() }}
                                            </flux:badge>
                                        @else
                                            -
                                        @endif
                                    @elseif ($columnKey === 'invoice_number')
                                        {{ $this->invoiceNumber($row) }}
                                    @elseif ($columnKey === 'invoice_total')
                                        {{ $this->invoiceTotal($row) }}
                                    @elseif ($columnKey === 'invoice_remaining')
                                        {{ $this->invoiceRemaining($row) }}
                                    @elseif ($columnKey === 'invoice_sent_at')
                                        {{ $this->invoiceSentAt($row) }}
                                    @elseif ($columnKey === 'invoice_reminder_sent_at')
                                        {{ $this->invoiceReminderSentAt($row) }}
                                    @elseif ($columnKey === 'invoice_synced_at')
                                        {{ $this->invoiceSyncedAt($row) }}
                                    @elseif ($columnKey === 'partner')
                                        {{ $this->selectedAthletePartner($row) }}
                                    @elseif ($columnKey === 'group')
                                        {{ $this->selectedAthleteGroup($row) }}
                                    @elseif ($columnKey === 'confirmed')
                                        @php($confirmed = $this->selectedAthleteConfirmed($row))
                                        @if ($confirmed === null)
                                            -
                                        @else
                                            <flux:badge size="sm" :color="$confirmed ? 'zinc' : 'red'">
                                                {{ $confirmed ? 'OK' : 'NOK' }}
                                            </flux:badge>
                                        @endif
                                    @else
                                        {{ $this->displayValue($row, $columnKey) }}
                                    @endif
                                </flux:table.cell>
                            @endforeach
                            @if ($role === 'athlete')
                                <flux:table.cell class="w-28 text-right">
                                    <flux:dropdown align="end">
                                        <flux:button
                                            variant="subtle"
                                            size="xs"
                                            icon="ellipsis-horizontal"
                                            aria-label="Dokumente"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadAthleteDocument"
                                            :disabled="! $this->documentDownloadsEnabled()"
                                        />
                                        <flux:menu>
                                            @if ($registration = $this->selectedAthleteRegistration($row))
                                                <flux:menu.group heading="Story-Bilder">
                                                    <flux:menu.item
                                                        href="{{ route('admin.story-image.download', [$registration, 'light']) }}"
                                                        icon="arrow-down-tray"
                                                    >
                                                        Hell herunterladen
                                                    </flux:menu.item>
                                                    <flux:menu.item
                                                        href="{{ route('admin.story-image.download', [$registration, 'dark']) }}"
                                                        icon="arrow-down-tray"
                                                    >
                                                        Dunkel herunterladen
                                                    </flux:menu.item>
                                                </flux:menu.group>
                                            @endif
                                            <flux:menu.item
                                                wire:click="downloadAthleteDocument({{ $row->id }}, 'welcome-letter')"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadAthleteDocument"
                                                icon="document-text"
                                                :disabled="! $this->documentDownloadsEnabled()"
                                            >
                                                Willkommensbrief
                                            </flux:menu.item>
                                            <flux:menu.item
                                                wire:click="downloadAthleteDocument({{ $row->id }}, 'personalized-flyer')"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadAthleteDocument"
                                                icon="document-text"
                                                :disabled="! $this->documentDownloadsEnabled()"
                                            >
                                                Personalisierter Flyer
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            @endif
                            <flux:table.cell class="w-1 whitespace-nowrap">
                                <div class="flex justify-end gap-1">
                                    @if ($role === 'donor' && $this->invoiceActionsEnabled())
                                        <flux:dropdown align="end">
                                            <flux:button
                                                variant="subtle"
                                                size="xs"
                                                icon="ellipsis-horizontal"
                                                square
                                                aria-label="Rechnungsaktionen"
                                            />
                                            <flux:menu>
                                                @if ($this->canCreateInvoiceForRow($row))
                                                    <flux:menu.item
                                                        wire:click="confirmCreateInvoice({{ $row->id }})"
                                                        icon="document-plus"
                                                    >
                                                        Rechnung erstellen
                                                    </flux:menu.item>
                                                @endif
                                                @if ($this->canDownloadInvoiceForRow($row))
                                                    <flux:menu.item
                                                        wire:click="downloadInvoicePdf({{ $row->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="downloadInvoicePdf"
                                                        icon="document-arrow-down"
                                                    >
                                                        Rechnung herunterladen
                                                    </flux:menu.item>
                                                @endif
                                                @if ($this->canSendInvoiceForRow($row))
                                                    <flux:menu.item
                                                        wire:click="sendInvoice({{ $row->id }})"
                                                        icon="paper-airplane"
                                                    >
                                                        Rechnung senden
                                                    </flux:menu.item>
                                                @endif
                                                @if ($this->canRemindInvoiceForRow($row))
                                                    <flux:menu.item
                                                        wire:click="sendInvoiceReminder({{ $row->id }})"
                                                        icon="bell-alert"
                                                    >
                                                        Zahlungserinnerung senden
                                                    </flux:menu.item>
                                                @endif
                                                @if (($weblingUrl = $this->invoiceWeblingUrl($row)) !== null)
                                                    <flux:menu.item
                                                        href="{{ $weblingUrl }}"
                                                        target="_blank"
                                                        icon="arrow-top-right-on-square"
                                                    >
                                                        Rechnung in Webling öffnen
                                                    </flux:menu.item>
                                                @endif
                                                @if ($this->canDeleteInvoiceForRow($row))
                                                    <flux:menu.item
                                                        wire:click="confirmDeleteInvoice({{ $row->id }})"
                                                        icon="trash"
                                                        variant="danger"
                                                    >
                                                        Rechnung löschen
                                                    </flux:menu.item>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        icon="pencil-square"
                                        square
                                        tooltip="Person bearbeiten"
                                        wire:click="$dispatchTo('admin-external-user-editor', 'open-external-user-editor', { externalUserId: {{ $row->id }} })"
                                    />
                                    @if ($role === 'athlete' && ($registration = $this->selectedAthleteRegistration($row)))
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            icon="clipboard-document"
                                            square
                                            tooltip="Anmeldung bearbeiten"
                                            wire:click="$dispatchTo('admin-athlete-registration-editor', 'open-athlete-registration-editor', { athleteRegistrationId: {{ $registration->id }} })"
                                        />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell colspan="99" class="text-center text-zinc-500">
                                <div class="mx-auto flex max-w-lg flex-col items-center gap-2 py-6">
                                    <flux:icon.magnifying-glass class="size-5 text-zinc-400" />
                                    @if (trim($search) !== '')
                                        <flux:text>Keine Treffer für "{{ $search }}".</flux:text>
                                        <flux:button variant="ghost" size="sm" wire:click="$set('search', '')"
                                            >Suche zurücksetzen</flux:button>
                                    @elseif ($eventSlug !== null && $eventSlug !== '')
                                        <flux:text>Keine {{ $this->roleLabel() }} für diesen Anlass vorhanden.</flux:text>
                                    @else
                                        <flux:text>Keine {{ $this->roleLabel() }} vorhanden.</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:checkbox.group>

        <x-slot:footer>
            <x-datatable.per-page-select />

            <flux:pagination :paginator="$external_users" />
        </x-slot:footer>
    </x-datatable>

    <livewire:admin-external-user-editor @external-user-saved="$refresh" />
    @if ($role === 'athlete')
        <livewire:admin-athlete-registration-editor @athlete-registration-saved="$refresh" />
    @endif

    @if ($role === 'donor')
        <flux:modal name="admin-person-invoice-confirm" class="min-w-[22rem]" wire:close="cancelInvoiceConfirm">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $this->invoiceConfirmHeading() }}</flux:heading>
                <flux:text>{{ $this->invoiceConfirmText() }}</flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="cancelInvoiceConfirm">Abbrechen</flux:button>
                    </flux:modal.close>
                    <flux:button
                        :variant="$this->confirmingInvoiceIsDestructive() ? 'danger' : 'primary'"
                        wire:click="runConfirmedInvoiceAction"
                        wire:loading.attr="disabled"
                        wire:target="runConfirmedInvoiceAction"
                    >
                        <span
                            wire:loading.remove
                            wire:target="runConfirmedInvoiceAction"
                        >{{ $this->invoiceConfirmButtonLabel() }}</span>
                        <span wire:loading wire:target="runConfirmedInvoiceAction">Wird ausgeführt...</span>
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
