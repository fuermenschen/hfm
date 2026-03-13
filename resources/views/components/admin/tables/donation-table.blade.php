<div>
    <x-admin.datatable>
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Spenden suchen..." icon="magnifying-glass" />

                <flux:dropdown>
                    <flux:button variant="ghost" icon="arrow-down-tray">Export</flux:button>
                    <flux:menu>
                        <flux:menu.group heading="Kompletter Datensatz">
                            <flux:menu.item wire:click="exportAll('xlsx')">Excel</flux:menu.item>
                            <flux:menu.item wire:click="exportAll('csv')">CSV</flux:menu.item>
                        </flux:menu.group>
                        <flux:menu.group heading="Ausgewählte Zeilen">
                            <flux:menu.item wire:click="exportSelected('xlsx')">Excel</flux:menu.item>
                            <flux:menu.item wire:click="exportSelected('csv')">CSV</flux:menu.item>
                        </flux:menu.group>
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown>
                    <flux:button variant="ghost" icon="adjustments-horizontal">Spalten</flux:button>
                    <flux:menu keep-open>
                        @foreach ($this->visibleColumnOptions() as $columnKey => $columnLabel)
                            <flux:menu.item keep-open wire:click="toggleColumn('{{ $columnKey }}')">
                                {{ $this->isColumnVisible($columnKey) ? '✓ ' : '' }}{{ $columnLabel }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            </div>

            @if ($this->selectedCount() > 0)
                <flux:button variant="subtle" wire:click="clearSelection">Auswahl entfernen ({{ $this->selectedCount() }})</flux:button>
            @endif
        </x-slot:toolbar>

        <flux:table class="min-w-max">
            <flux:table.columns>
                <flux:table.column>
                    <input
                        type="checkbox"
                        class="size-4"
                        @checked(count($pageIds) > 0 && count(array_intersect($pageIds, $checkboxValues)) === count($pageIds))
                        wire:click="toggleSelectPage({{ json_encode($pageIds) }})"
                    />
                </flux:table.column>
                @if ($this->isColumnVisible('donator'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'donator', 'label' => 'Spender:in'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('athlete'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'athlete', 'label' => 'Sportler:in'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('verified'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'verified', 'label' => 'Bestätigt'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('amount_per_round'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'amount_per_round', 'label' => 'Betrag pro Runde'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('estimated'))
                    <flux:table.column>Geschätzter Betrag</flux:table.column>
                @endif
                @if ($this->isColumnVisible('actual'))
                    <flux:table.column>Tatsächlicher Betrag</flux:table.column>
                @endif
                @if ($this->isColumnVisible('amount_min'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'amount_min', 'label' => 'Minimaler Betrag'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('amount_max'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'amount_max', 'label' => 'Maximaler Betrag'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('created_at'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'created_at', 'label' => 'Erstellt am'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('comment'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'comment', 'label' => 'Kommentar'])</flux:table.column>
                @endif
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($donations as $donation)
                    @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                    <flux:table.row wire:key="donation-{{ $donation->id }}" class="{{ $rowClass }}">
                        <flux:table.cell>
                            <input type="checkbox" class="size-4" wire:model.live="checkboxValues" value="{{ $donation->id }}" />
                        </flux:table.cell>
                        @if ($this->isColumnVisible('donator'))
                            <flux:table.cell>{{ $donation->donator->privacy_name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('athlete'))
                            <flux:table.cell>{{ $donation->athlete->privacy_name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('verified'))
                            <flux:table.cell>{{ $donation->verified ? 'Ja' : 'Nein' }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('amount_per_round'))
                            <flux:table.cell>Fr. {{ number_format($donation->amount_per_round, 2, '.', "'") }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('estimated'))
                            <flux:table.cell>Fr. {{ number_format($this->estimatedAmount($donation), 2, '.', "'") }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('actual'))
                            <flux:table.cell>Fr. {{ number_format($this->actualAmount($donation), 2, '.', "'") }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('amount_min'))
                            <flux:table.cell>{{ $donation->amount_min ? 'Fr. '.number_format($donation->amount_min, 2, '.', "'") : 'unbegrenzt' }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('amount_max'))
                            <flux:table.cell>{{ $donation->amount_max ? 'Fr. '.number_format($donation->amount_max, 2, '.', "'") : 'unbegrenzt' }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('created_at'))
                            <flux:table.cell>{{ \Illuminate\Support\Carbon::parse($donation->created_at)->format('d.m.Y') }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('comment'))
                            <flux:table.cell>
                                <flux:tooltip content="{{ $donation->comment }}">
                                    <span class="block max-w-60 truncate">{{ $this->truncateText($donation->comment, 48) }}</span>
                                </flux:tooltip>
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="99" class="text-center text-zinc-500">Keine Spenden gefunden.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <x-slot:footer>
            <div class="flex items-center gap-2">
                <flux:text>Pro Seite</flux:text>
                <flux:select wire:model.live="perPage" class="w-24">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </flux:select>
            </div>

            <flux:pagination :paginator="$donations" />
        </x-slot:footer>
    </x-admin.datatable>
</div>
