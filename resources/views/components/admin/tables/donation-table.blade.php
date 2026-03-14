<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.partials.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Spenden suchen..." icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.partials.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-datatable.partials.export-dropdown />
                        <x-datatable.partials.column-visibility-dropdown :column-options="$this->visibleColumnOptions()" />
                    </div>
                </x-slot:bottomLeft>
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
                @forelse ($donations as $donation)
                    @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                    <flux:table.row wire:key="donation-{{ $donation->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}" class="{{ $rowClass }}">
                        <flux:table.cell>
                            <flux:field variant="inline">
                                <flux:checkbox value="{{ $donation->id }}" />
                            </flux:field>
                        </flux:table.cell>
                        @foreach ($visibleColumns as $columnKey => $columnDefinition)
                            @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                            @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                            <flux:table.cell class="{{ $cellClass }}">
                                @switch($columnKey)
                                    @case('donor')
                                        {{ $donation->donor->privacy_name }}
                                        @break

                                    @case('athlete')
                                        {{ $donation->athlete->privacy_name }}
                                        @break

                                    @case('verified')
                                        {{ $donation->verified ? 'Ja' : 'Nein' }}
                                        @break

                                    @case('amount_per_round')
                                        {{ $this->formatMoney($donation->amount_per_round) }}
                                        @break

                                    @case('estimated')
                                        {{ $this->formatMoney($this->estimatedAmount($donation)) }}
                                        @break

                                    @case('actual')
                                        {{ $this->formatMoney($this->actualAmount($donation)) }}
                                        @break

                                    @case('amount_min')
                                        {{ $this->formatMoneyOrUnlimited($donation->amount_min) }}
                                        @break

                                    @case('amount_max')
                                        {{ $this->formatMoneyOrUnlimited($donation->amount_max) }}
                                        @break

                                    @case('created_at')
                                        {{ $this->formatDate($donation->created_at) }}
                                        @break

                                    @case('comment')
                                        <flux:tooltip content="{{ $this->fallbackText($donation->comment) }}">
                                            <span class="block max-w-60 truncate">{{ $this->truncateText($donation->comment, (int) ($columnDefinition['truncate'] ?? 48)) }}</span>
                                        </flux:tooltip>
                                        @break
                                @endswitch
                            </flux:table.cell>
                        @endforeach
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
                                    <flux:text>Keine Spenden vorhanden.</flux:text>
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

            <flux:pagination :paginator="$donations" />
        </x-slot:footer>
    </x-datatable>
</div>
