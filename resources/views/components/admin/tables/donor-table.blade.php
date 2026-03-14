<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.partials.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Spender:innen suchen..." icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.partials.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-datatable.partials.export-dropdown />

                        <flux:button variant="ghost" size="sm" icon="banknotes" wire:click="checkPaymentStatus" wire:target="checkPaymentStatus" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="checkPaymentStatus">Zahlungsstatus prüfen</span>
                            <span wire:loading wire:target="checkPaymentStatus">Prüfe Zahlungsstatus...</span>
                        </flux:button>

                        <x-datatable.partials.column-visibility-dropdown :column-options="$this->visibleColumnOptions()" />
                    </div>
                </x-slot:bottomLeft>

                <x-slot:bottomRight>
                    <x-datatable.partials.bulk-action-buttons :actions="$this->donorBulkActions()" />
                </x-slot:bottomRight>
            </x-datatable.partials.toolbar-grid>
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
                            @include('components.datatable.partials.sortable-header', ['column' => $columnKey, 'label' => $columnDefinition['label']])
                        @else
                            <span>{{ $columnDefinition['label'] }}</span>
                        @endif
                    </flux:table.column>
                @endforeach
                <x-admin.tables.partials.actions-column header />
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
                @forelse ($donors as $donor)
                    <flux:table.row wire:key="donor-{{ $donor->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
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
                                        {{ $this->formatMoney($donor->invoice_total) }}
                                        @break

                                    @case('created_at')
                                        {{ $this->formatDate($donor->created_at) }}
                                        @break

                                    @case('email')
                                        <flux:tooltip content="{{ $this->fallbackText($donor->email) }}">
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
                                        <flux:tooltip content="{{ $this->fallbackText($donor->address) }}">
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
                                        {{ $this->formatDateTime($donor->invoice_sent_at) }}
                                        @break

                                    @case('invoice_reminder_sent_at')
                                        {{ $this->formatDateTime($donor->invoice_reminder_sent_at) }}
                                        @break
                                @endswitch
                            </flux:table.cell>
                        @endforeach
                        <x-admin.tables.partials.actions-column>
                            @include('components.admin.tables.partials.donor-row-actions', ['row' => $donor, 'actionGroups' => $this->donorRowActionGroups($donor)])
                        </x-admin.tables.partials.actions-column>
                    </flux:table.row>
                @empty
                    <flux:table.row wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                        <flux:table.cell colspan="99" class="text-center">
                            <div class="mx-auto flex max-w-lg flex-col items-center gap-2 py-6">
                                <flux:icon.magnifying-glass class="size-5" />
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
            <x-datatable.partials.per-page-select />

            <flux:pagination :paginator="$donors" />
        </x-slot:footer>
    </x-datatable>
</div>
