<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.partials.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Sportler:innen suchen..." icon="magnifying-glass" />
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
                <x-admin.tables.partials.actions-column header />
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
                @forelse ($athletes as $athlete)
                    <flux:table.row wire:key="athlete-{{ $athlete->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                        <flux:table.cell>
                            <flux:field variant="inline">
                                <flux:checkbox value="{{ $athlete->id }}" />
                            </flux:field>
                        </flux:table.cell>
                        @foreach ($visibleColumns as $columnKey => $columnDefinition)
                            @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                            @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                            <flux:table.cell class="{{ $cellClass }}">
                                @switch($columnKey)
                                    @case('first_name')
                                        {{ $athlete->first_name }}
                                        @break

                                    @case('last_name')
                                        {{ $athlete->last_name }}
                                        @break

                                    @case('verified')
                                        {{ $athlete->verified ? 'Ja' : 'Nein' }}
                                        @break

                                    @case('sport_type')
                                        {{ $athlete->sportType?->name }}
                                        @break

                                    @case('partner')
                                        {{ $athlete->partner?->name }}
                                        @break

                                    @case('rounds_estimated')
                                        {{ $athlete->rounds_estimated }}
                                        @break

                                    @case('rounds_done')
                                        <div class="flex items-center gap-2">
                                            <flux:input type="number" class="w-20" wire:model.blur="roundsDoneInputs.{{ $athlete->id }}" />
                                            <flux:button variant="subtle" size="sm" wire:click="saveRoundsDone({{ $athlete->id }})" wire:target="saveRoundsDone({{ $athlete->id }})" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="saveRoundsDone({{ $athlete->id }})">Speichern</span>
                                                <span wire:loading wire:target="saveRoundsDone({{ $athlete->id }})">Speichert...</span>
                                            </flux:button>
                                        </div>
                                        @break

                                    @case('donations_count')
                                        {{ $athlete->donations_count }}
                                        @break

                                    @case('estimated_total')
                                        {{ $this->formatMoney($this->estimatedDonationsTotal($athlete)) }}
                                        @break

                                    @case('actual_total')
                                        {{ $this->formatMoney($this->actualDonationsTotal($athlete)) }}
                                        @break

                                    @case('created_at')
                                        {{ $this->formatDate($athlete->created_at) }}
                                        @break

                                    @case('adult')
                                        {{ $athlete->adult ? 'Ja' : 'Nein' }}
                                        @break

                                    @case('phone_number')
                                        {{ $athlete->phone_number }}
                                        @break

                                    @case('email')
                                        <flux:tooltip content="{{ $this->fallbackText($athlete->email) }}">
                                            <span class="block max-w-52 truncate">{{ $this->truncateText($athlete->email, (int) ($columnDefinition['truncate'] ?? 52)) }}</span>
                                        </flux:tooltip>
                                        @break

                                    @case('address')
                                        <flux:tooltip content="{{ $this->fallbackText($athlete->address) }}">
                                            <span class="block max-w-56 truncate">{{ $this->truncateText($athlete->address, (int) ($columnDefinition['truncate'] ?? 44)) }}</span>
                                        </flux:tooltip>
                                        @break

                                    @case('zip_code')
                                        {{ $athlete->zip_code }}
                                        @break

                                    @case('city')
                                        {{ $athlete->city }}
                                        @break

                                    @case('comment')
                                        <flux:tooltip content="{{ $this->fallbackText($athlete->comment) }}">
                                            <span class="block max-w-60 truncate">{{ $this->truncateText($athlete->comment, (int) ($columnDefinition['truncate'] ?? 48)) }}</span>
                                        </flux:tooltip>
                                        @break
                                @endswitch
                            </flux:table.cell>
                        @endforeach
                        <x-admin.tables.partials.actions-column>
                            <flux:dropdown>
                                <flux:button variant="subtle" size="xs" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="downloadWelcomeLetter({{ $athlete->id }})">Brief</flux:menu.item>
                                    <flux:menu.item wire:click="downloadPersonalizedFlyerTemplate({{ $athlete->id }})">Flyer</flux:menu.item>
                                    <flux:menu.item :href="route('show-athlete', ['login_token' => $athlete->login_token])" target="_blank">Login</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </x-admin.tables.partials.actions-column>
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
                                    <flux:text>Keine Sportler:innen vorhanden.</flux:text>
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

            <flux:pagination :paginator="$athletes" />
        </x-slot:footer>
    </x-datatable>
</div>
