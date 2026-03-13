<div>
    <x-admin.datatable>
        <x-slot:toolbar>
            <x-admin.tables.partials.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Spender:innen suchen..." icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-admin.tables.partials.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-admin.tables.partials.export-dropdown />

                        <flux:button variant="ghost" size="sm" icon="banknotes" wire:click="checkPaymentStatus" wire:target="checkPaymentStatus" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="checkPaymentStatus">Zahlungsstatus prüfen</span>
                            <span wire:loading wire:target="checkPaymentStatus">Prüfe Zahlungsstatus...</span>
                        </flux:button>

                        <x-admin.tables.partials.column-visibility-dropdown :column-options="$this->visibleColumnOptions()" />
                    </div>
                </x-slot:bottomLeft>

                <x-slot:bottomRight>
                    <x-admin.tables.partials.bulk-actions>
                        <flux:button size="sm" wire:click="bulkCreateInvoice" wire:target="bulkCreateInvoice" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="bulkCreateInvoice">Rechnungen erstellen</span>
                            <span wire:loading wire:target="bulkCreateInvoice">Erstelle Rechnungen...</span>
                        </flux:button>
                        <flux:button size="sm" wire:click="bulkDownloadInvoice" wire:target="bulkDownloadInvoice" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="bulkDownloadInvoice">Rechnungen herunterladen</span>
                            <span wire:loading wire:target="bulkDownloadInvoice">Bereite ZIP vor...</span>
                        </flux:button>
                        <flux:button size="sm" wire:click="bulkSendInvoice" wire:target="bulkSendInvoice" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="bulkSendInvoice">Rechnungen senden</span>
                            <span wire:loading wire:target="bulkSendInvoice">Sende Rechnungen...</span>
                        </flux:button>
                        <flux:button size="sm" wire:click="bulkSendInvoiceReminder" wire:target="bulkSendInvoiceReminder" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="bulkSendInvoiceReminder">Erinnerungen senden</span>
                            <span wire:loading wire:target="bulkSendInvoiceReminder">Sende Erinnerungen...</span>
                        </flux:button>
                    </x-admin.tables.partials.bulk-actions>
                </x-slot:bottomRight>
            </x-admin.tables.partials.toolbar-grid>
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
                            @include('components.admin.tables.partials.sortable-header', ['column' => $columnKey, 'label' => $columnDefinition['label']])
                        @else
                            <span>{{ $columnDefinition['label'] }}</span>
                        @endif
                    </flux:table.column>
                @endforeach
                <flux:table.column class="sticky right-0 z-10 w-14 min-w-14 align-middle border-l border-zinc-200 bg-white text-center dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-center">
                        <flux:icon.cog-6-tooth class="size-4" />
                    </div>
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                <flux:table.row wire:loading.delay.short wire:target="{{ $this->tableLoadingTargets() }}">
                    <flux:table.cell colspan="99" class="text-center">
                        <div class="flex items-center justify-center gap-2 py-4 text-sm text-zinc-500">
                            <flux:icon.arrow-path class="size-4 animate-spin" />
                            Tabelle wird aktualisiert...
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @forelse ($donors as $donor)
                    @php($invoiceTotal = (float) ($donor->invoice_total ?? 0))
                    @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                    <flux:table.row wire:key="donor-{{ $donor->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}" class="{{ $rowClass }}">
                        <flux:table.cell>
                            <flux:field variant="inline">
                                <flux:checkbox value="{{ $donor->id }}" />
                            </flux:field>
                        </flux:table.cell>
                        @foreach ($visibleColumns as $columnKey => $columnDefinition)
                            @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                            @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                            <flux:table.cell class="{{ $cellClass }}">
                                @switch($columnKey)
                                    @case('don_id')
                                        DON-{{ sprintf('25%04d', $donor->id) }}
                                        @break

                                    @case('first_name')
                                        {{ $donor->first_name }}
                                        @break

                                    @case('last_name')
                                        {{ $donor->last_name }}
                                        @break

                                    @case('donations_count')
                                        {{ $donor->donations_count }}
                                        @break

                                    @case('invoice_total')
                                        Fr. {{ number_format($invoiceTotal, 2, '.', "'") }}
                                        @break

                                    @case('created_at')
                                        {{ \Illuminate\Support\Carbon::parse($donor->created_at)->format('d.m.Y') }}
                                        @break

                                    @case('email')
                                        <flux:tooltip content="{{ $donor->email }}">
                                            <span class="block max-w-52 truncate">{{ $this->truncateText($donor->email, (int) ($columnDefinition['truncate'] ?? 52)) }}</span>
                                        </flux:tooltip>
                                        @break

                                    @case('phone_number')
                                        {{ $donor->phone_number }}
                                        @break

                                    @case('country')
                                        {{ $donor->country_of_residence }}
                                        @break

                                    @case('address')
                                        <flux:tooltip content="{{ $donor->address }}">
                                            <span class="block max-w-56 truncate">{{ $this->truncateText($donor->address, (int) ($columnDefinition['truncate'] ?? 44)) }}</span>
                                        </flux:tooltip>
                                        @break

                                    @case('zip_code')
                                        {{ $donor->zip_code }}
                                        @break

                                    @case('city')
                                        {{ $donor->city }}
                                        @break

                                    @case('invoice_status')
                                        {{ $this->invoiceStatusLabel($donor) }}
                                        @break

                                    @case('invoice_sent_at')
                                        {{ $donor->invoice_sent_at ? \Illuminate\Support\Carbon::parse($donor->invoice_sent_at)->format('d.m.Y H:i') : '-' }}
                                        @break

                                    @case('invoice_reminder_sent_at')
                                        {{ $donor->invoice_reminder_sent_at ? \Illuminate\Support\Carbon::parse($donor->invoice_reminder_sent_at)->format('d.m.Y H:i') : '-' }}
                                        @break
                                @endswitch
                            </flux:table.cell>
                        @endforeach
                        <flux:table.cell class="sticky right-0 z-10 w-14 min-w-14 align-middle border-l border-zinc-200 {{ $rowClass }} text-center dark:border-zinc-700">
                            @include('components.admin.tables.partials.donator-row-actions', ['row' => $donor])
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                        <flux:table.cell colspan="99" class="text-center text-zinc-500">
                            <div class="mx-auto flex max-w-lg flex-col items-center gap-2 py-6">
                                <flux:icon.magnifying-glass class="size-5 text-zinc-400" />
                                @if (trim($search) !== '')
                                    <flux:text>Keine Treffer für "{{ $search }}".</flux:text>
                                    <flux:button variant="ghost" size="sm" wire:click="$set('search', '')">Suche zurücksetzen</flux:button>
                                @else
                                    <flux:text>Keine Spender:innen vorhanden.</flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
            </flux:table>
        </flux:checkbox.group>

        <x-slot:footer>
            <x-admin.tables.partials.per-page-select />

            <flux:pagination :paginator="$donors" />
        </x-slot:footer>
    </x-admin.datatable>
</div>
