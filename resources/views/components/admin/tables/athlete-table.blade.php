<div>
    <x-admin.datatable>
        <x-slot:toolbar>
            <x-admin.tables.partials.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Sportler:innen suchen..." icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-admin.tables.partials.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="arrow-down-tray">Export</flux:button>
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
                            <flux:button variant="ghost" size="sm" icon="adjustments-horizontal">Spalten</flux:button>
                            <flux:menu keep-open>
                                @foreach ($this->visibleColumnOptions() as $columnKey => $columnLabel)
                                    <flux:menu.item keep-open wire:click="toggleColumn('{{ $columnKey }}')">
                                        {{ $this->isColumnVisible($columnKey) ? '✓ ' : '' }}{{ $columnLabel }}
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </x-slot:bottomLeft>
            </x-admin.tables.partials.toolbar-grid>
        </x-slot:toolbar>

        <flux:checkbox.group wire:model.live="checkboxValues">
            <flux:table class="min-w-max">
            <flux:table.columns>
                <flux:table.column>
                    <flux:field variant="inline">
                        <flux:checkbox.all />
                    </flux:field>
                </flux:table.column>
                @if ($this->isColumnVisible('first_name'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'first_name', 'label' => 'Vorname'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('last_name'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'last_name', 'label' => 'Nachname'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('verified'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'verified', 'label' => 'Bestätigt'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('sport_type'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'sport_type', 'label' => 'Sportart'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('partner'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'partner', 'label' => 'Partner'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('rounds_estimated'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'rounds_estimated', 'label' => 'Runden geschätzt'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('rounds_done'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'rounds_done', 'label' => 'Runden gemacht'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('donations_count'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'donations_count', 'label' => 'Spenden'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('estimated_total'))
                    <flux:table.column>Geschätzte Spenden</flux:table.column>
                @endif
                @if ($this->isColumnVisible('actual_total'))
                    <flux:table.column>Tatsächliche Spenden</flux:table.column>
                @endif
                @if ($this->isColumnVisible('created_at'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'created_at', 'label' => 'Anmeldung'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('adult'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'adult', 'label' => 'Erwachsen'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('phone_number'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'phone_number', 'label' => 'Telefon'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('email'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'email', 'label' => 'E-Mail'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('address'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'address', 'label' => 'Adresse'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('zip_code'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'zip_code', 'label' => 'PLZ'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('city'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'city', 'label' => 'Ort'])</flux:table.column>
                @endif
                @if ($this->isColumnVisible('comment'))
                    <flux:table.column>@include('components.admin.tables.partials.sortable-header', ['column' => 'comment', 'label' => 'Kommentar'])</flux:table.column>
                @endif
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
                @forelse ($athletes as $athlete)
                    @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                    <flux:table.row wire:key="athlete-{{ $athlete->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}" class="{{ $rowClass }}">
                        <flux:table.cell>
                            <flux:field variant="inline">
                                <flux:checkbox value="{{ $athlete->id }}" />
                            </flux:field>
                        </flux:table.cell>
                        @if ($this->isColumnVisible('first_name'))
                            <flux:table.cell>{{ $athlete->first_name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('last_name'))
                            <flux:table.cell>{{ $athlete->last_name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('verified'))
                            <flux:table.cell>{{ $athlete->verified ? 'Ja' : 'Nein' }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('sport_type'))
                            <flux:table.cell>{{ $athlete->sportType?->name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('partner'))
                            <flux:table.cell>{{ $athlete->partner?->name }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('rounds_estimated'))
                            <flux:table.cell>{{ $athlete->rounds_estimated }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('rounds_done'))
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:input type="number" class="w-20" wire:model.blur="roundsDoneInputs.{{ $athlete->id }}" />
                                    <flux:button variant="subtle" size="sm" wire:click="saveRoundsDone({{ $athlete->id }})" wire:target="saveRoundsDone({{ $athlete->id }})" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="saveRoundsDone({{ $athlete->id }})">Speichern</span>
                                        <span wire:loading wire:target="saveRoundsDone({{ $athlete->id }})">Speichert...</span>
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('donations_count'))
                            <flux:table.cell>{{ $athlete->donations_count }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('estimated_total'))
                            <flux:table.cell>Fr. {{ number_format($this->estimatedDonationsTotal($athlete), 2, '.', "'") }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('actual_total'))
                            <flux:table.cell>Fr. {{ number_format($this->actualDonationsTotal($athlete), 2, '.', "'") }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('created_at'))
                            <flux:table.cell>{{ \Illuminate\Support\Carbon::parse($athlete->created_at)->format('d.m.Y') }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('adult'))
                            <flux:table.cell>{{ $athlete->adult ? 'Ja' : 'Nein' }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('phone_number'))
                            <flux:table.cell>{{ $athlete->phone_number }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('email'))
                            <flux:table.cell>
                                <flux:tooltip content="{{ $athlete->email }}">
                                    <span class="block max-w-52 truncate">{{ $athlete->email }}</span>
                                </flux:tooltip>
                            </flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('address'))
                            <flux:table.cell>
                                <flux:tooltip content="{{ $athlete->address }}">
                                    <span class="block max-w-56 truncate">{{ $this->truncateText($athlete->address, 44) }}</span>
                                </flux:tooltip>
                            </flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('zip_code'))
                            <flux:table.cell>{{ $athlete->zip_code }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('city'))
                            <flux:table.cell>{{ $athlete->city }}</flux:table.cell>
                        @endif
                        @if ($this->isColumnVisible('comment'))
                            <flux:table.cell>
                                <flux:tooltip content="{{ $athlete->comment }}">
                                    <span class="block max-w-60 truncate">{{ $this->truncateText($athlete->comment, 48) }}</span>
                                </flux:tooltip>
                            </flux:table.cell>
                        @endif
                        <flux:table.cell class="sticky right-0 z-10 w-14 min-w-14 align-middle border-l border-zinc-200 {{ $rowClass }} text-center dark:border-zinc-700">
                            <div class="flex items-center justify-center">
                                <flux:dropdown>
                                    <flux:button variant="subtle" size="xs" icon="ellipsis-horizontal" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="downloadWelcomeLetter({{ $athlete->id }})">Brief</flux:menu.item>
                                        <flux:menu.item wire:click="downloadPersonalizedFlyerTemplate({{ $athlete->id }})">Flyer</flux:menu.item>
                                        <flux:menu.item :href="route('show-athlete', ['login_token' => $athlete->login_token])" target="_blank">Login</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
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

            <flux:pagination :paginator="$athletes" />
        </x-slot:footer>
    </x-admin.datatable>
</div>
