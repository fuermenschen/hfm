<div wire:poll.5s class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1">
            @foreach ($this->statusFilters() as $key => $label)
                <flux:button
                    size="xs"
                    :variant="$statusFilter === $key ? 'primary' : 'ghost'"
                    wire:click="$set('statusFilter', '{{ $key }}')"
                >
                    {{ $label }} <span class="tabular-nums">({{ $counts[$key] }})</span>
                </flux:button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Name oder Startnummer..."
                icon="magnifying-glass"
                size="sm"
                class="w-56"
            />
            <flux:select
                wire:model.live="eventSlug"
                variant="listbox"
                searchable
                placeholder="Anlass wählen"
                size="sm"
                class="w-60"
            >
                @foreach ($events as $event)
                    <flux:select.option :value="$event->slug">
                        {{ $event->title }} ({{ $event->slug }}){{ $event->is_published ? '' : ' - NICHT VERÖFFENTLICHT' }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:dropdown>
            <flux:button variant="subtle" icon="bolt" :disabled="! $eventSlug"> Alle … </flux:button>
            <flux:popover class="w-80 space-y-3">
                <div class="space-y-2">
                    <flux:button
                        variant="primary"
                        class="w-full"
                        icon="play"
                        wire:click="confirmBatch('start')"
                        wire:loading.attr="disabled"
                        wire:target="confirmBatch,runBatch"
                    >
                        Alle starten
                    </flux:button>
                    <flux:button
                        variant="subtle"
                        class="w-full"
                        icon="flag"
                        wire:click="confirmBatch('finish')"
                        wire:loading.attr="disabled"
                        wire:target="confirmBatch,runBatch"
                    >
                        Alle als Fertig markieren
                    </flux:button>
                    <flux:separator />
                    <flux:button
                        variant="ghost"
                        class="w-full text-red-600! dark:text-red-400!"
                        icon="arrow-path"
                        wire:click="confirmBatch('reset')"
                        wire:loading.attr="disabled"
                        wire:target="confirmBatch,runBatch"
                    >
                        Alle zurücksetzen
                    </flux:button>
                </div>
            </flux:popover>
        </flux:dropdown>

        <flux:text class="text-sm text-zinc-500">
            <flux:icon.hashtag class="inline size-4" />
            {{ $totalRounds }} Runden total
        </flux:text>
    </div>

    @if ($eventSlug === null || $eventSlug === '')
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>Bitte oben einen Anlass auswählen.</flux:callout.text>
        </flux:callout>
    @elseif ($registrations->isEmpty())
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>
                Keine Sportler:innen für diesen Filter.
                @if (trim($search) !== '')
                    Suche zurücksetzen, um mehr zu sehen.
                @endif
            </flux:callout.text>
        </flux:callout>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach ($registrations as $registration)
                @php($stripClass = match ($registration->event_state->value) {
                    'running' => 'animate-pulse bg-green-500',
                    'finished' => 'bg-zinc-400',
                    default => 'bg-blue-500',
                })
                <div
                    wire:key="athlete-{{ $registration->id }}"
                    class="relative flex h-fit w-40 flex-col items-center overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 {{ $registration->event_state->value === 'finished' ? 'opacity-60' : '' }}"
                >
                    <span class="{{ $stripClass }} absolute inset-y-0 left-0 w-1.5"></span>

                    <div class="flex w-full flex-col items-center gap-1 py-2 pr-2 pl-4">
                        <span class="font-mono text-lg leading-none font-bold tabular-nums">
                            {{ $registration->start_number !== null ? '#'.$registration->start_number : '–' }}
                        </span>

                        <span
                            class="w-full truncate text-center text-xs text-zinc-500"
                            title="{{ $registration->externalUser->privacy_name }}"
                        >
                            {{ $registration->externalUser->privacy_name }}
                        </span>

                        <span class="flex items-baseline gap-0.5">
                            <span class="text-3xl leading-none font-bold tabular-nums">{{ $registration->rounds_done }}</span>
                            <span class="text-sm font-medium text-zinc-400 tabular-nums">/{{ $registration->rounds_estimated }}</span>
                        </span>

                        <div class="mt-1 flex w-full items-center gap-1">
                            @if ($registration->event_state->value === 'not_started')
                                <flux:button
                                    variant="subtle"
                                    icon="play"
                                    size="sm"
                                    class="h-9 w-full"
                                    wire:click="start({{ $registration->id }})"
                                >
                                    Start
                                </flux:button>
                            @else
                                <flux:button
                                    variant="primary"
                                    size="sm"
                                    class="h-9 flex-1 text-base font-semibold"
                                    wire:click="addRound({{ $registration->id }})"
                                    :disabled="$registration->event_state->value === 'finished'"
                                >
                                    +1
                                </flux:button>
                                @if ($registration->event_state->value === 'finished')
                                    <flux:button
                                        variant="subtle"
                                        icon="arrow-path"
                                        square
                                        size="sm"
                                        class="h-9 w-9"
                                        tooltip="Erneut starten"
                                        wire:click="reactivate({{ $registration->id }})"
                                    />
                                @else
                                    <flux:button
                                        variant="subtle"
                                        icon="flag"
                                        square
                                        size="sm"
                                        class="h-9 w-9"
                                        tooltip="Fertig markieren"
                                        wire:click="confirmFinish({{ $registration->id }})"
                                    />
                                @endif
                            @endif
                            <flux:dropdown align="end">
                                <flux:button
                                    variant="ghost"
                                    icon="ellipsis-horizontal"
                                    square
                                    size="sm"
                                    class="h-9 w-9"
                                    aria-label="Weitere Aktionen"
                                />
                                <flux:menu>
                                    <flux:menu.item icon="minus" wire:click="removeRound({{ $registration->id }})">
                                        Runde entfernen
                                    </flux:menu.item>
                                    <flux:menu.item
                                        icon="arrow-path"
                                        wire:click="resetAthlete({{ $registration->id }})"
                                    >
                                        Runden und Status zurücksetzen
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="round-counter-confirm-batch">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $this->batchLabels()[$confirmingBatch]['heading'] ?? '' }}</flux:heading>
            <flux:text>{{ $this->batchLabels()[$confirmingBatch]['text'] ?? '' }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="primary"
                    wire:click="runBatch"
                    wire:loading.attr="disabled"
                    wire:target="runBatch"
                >
                    {{ $this->batchLabels()[$confirmingBatch]['button'] ?? '' }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="round-counter-confirm-finish">
        <div class="space-y-4">
            <flux:heading size="lg">Als fertig markieren?</flux:heading>
            <flux:text> Die Sportler:in wird als fertig markiert und aus der Standardansicht ausgeblendet. </flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="finish" wire:loading.attr="disabled" wire:target="finish">
                    Fertig markieren
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
