<div class="space-y-8" data-admin-settings-root>
    <flux:tab.group>
        <flux:tabs wire:model="activeTab" scrollable scrollable:fade>
            <flux:tab name="overview" icon="home">Übersicht</flux:tab>

            @foreach ($classes as $fqcn => $meta)
                @php
                    $tabName = 'settings-'.md5($fqcn);
                    $classTitle = $meta['title'] ?? class_basename($fqcn);
                    $tabIcon = str_contains(strtolower($classTitle), 'api') ? 'circle-stack' : 'cog-6-tooth';
                @endphp
                <flux:tab :name="$tabName" :icon="$tabIcon">{{ $classTitle }}</flux:tab>
            @endforeach
        </flux:tabs>

        <flux:tab.panel name="overview">
            <div class="mt-5 space-y-4">
                <flux:text class="text-sm opacity-85">Hier verwaltest du systemweite Konfigurationen. Bitte Änderungen bewusst vornehmen.</flux:text>

                <flux:callout>
                    <flux:callout.heading icon="exclamation-triangle">Änderungen gelten sofort</flux:callout.heading>
                    <flux:callout.text>Gespeicherte Einstellungen werden unmittelbar aktiv und können das Verhalten im gesamten System beeinflussen.</flux:callout.text>
                </flux:callout>
            </div>
        </flux:tab.panel>

        @foreach ($classes as $fqcn => $meta)
            @php
                $tabName = 'settings-'.md5($fqcn);
                $classTitle = $meta['title'] ?? class_basename($fqcn);
                $classDesc = $meta['description'] ?? null;
                $classTargets = collect(array_keys($meta['settings'] ?? []))
                    ->map(fn (string $name) => "values.$fqcn.$name")
                    ->implode(',');
                $classParam = addslashes($fqcn);
            @endphp

            <flux:tab.panel :name="$tabName">
                <div class="mt-5">
                    <flux:card class="space-y-6">
                        <div class="space-y-1">
                            <div class="flex items-center justify-between gap-3">
                                <flux:heading size="lg">{{ $classTitle }}</flux:heading>
                                <flux:text wire:dirty wire:target="{{ $classTargets }}" class="text-xs text-accent">Ungespeichert</flux:text>
                            </div>
                            @if (! empty($classDesc))
                                <flux:subheading>{{ $classDesc }}</flux:subheading>
                            @endif
                            <flux:subheading class="text-xs opacity-60">{{ $fqcn }}</flux:subheading>
                        </div>

                        <div class="space-y-6">
                            @foreach ($meta['settings'] as $name => $info)
                                @php
                                    $type = $info['type'] ?? null;
                                    $desc = $info['description'] ?? null;
                                    $title = $info['title'] ?? $name;
                                    $encrypted = $info['encrypted'] ?? false;
                                    $options = is_array($info['options'] ?? null) ? $info['options'] : null;
                                @endphp

                                <flux:field>
                                    <span>
                                        <flux:label>{{ $title }}</flux:label>
                                        <span wire:dirty wire:target="values.{{ $fqcn }}.{{ $name }}" class="ml-1 text-xs opacity-70 text-accent">(ungespeichert)</span>
                                    </span>

                                    @switch($type)
                                        @case('?int')
                                        @case('int')
                                            @if ($options !== null)
                                                <flux:select wire:model="values.{{ $fqcn }}.{{ $name }}" variant="listbox" class="w-full max-w-2xl">
                                                    <flux:select.option value="">- Bitte wählen -</flux:select.option>
                                                    @foreach($options as $optionLabel => $optionValue)
                                                        <flux:select.option :value="(string) $optionValue">{{ $optionLabel }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                            @elseif ($encrypted)
                                                <flux:input type="password" viewable wire:model="values.{{ $fqcn }}.{{ $name }}" class="w-full max-w-md" />
                                            @else
                                                <flux:input
                                                    type="number"
                                                    wire:model="values.{{ $fqcn }}.{{ $name }}"
                                                    step="1"
                                                    class="w-full max-w-md"
                                                />
                                            @endif
                                            @break

                                        @case('bool')
                                            <flux:switch wire:model="values.{{ $fqcn }}.{{ $name }}" />
                                            @break

                                        @case('float')
                                        @case('double')
                                            @if ($encrypted)
                                                <flux:input type="password" viewable wire:model="values.{{ $fqcn }}.{{ $name }}" class="w-full max-w-md" />
                                            @else
                                                <flux:input
                                                    type="number"
                                                    wire:model="values.{{ $fqcn }}.{{ $name }}"
                                                    step="{{ in_array($type, ['float', 'double']) ? '0.01' : '1' }}"
                                                    class="w-full max-w-md"
                                                />
                                            @endif
                                            @break

                                        @case('array')
                                            <flux:textarea
                                                wire:model="values.{{ $fqcn }}.{{ $name }}"
                                                placeholder="JSON oder komma-getrennte Werte"
                                                class="w-full max-w-2xl"
                                            />
                                            @break

                                        @default
                                            <flux:input
                                                type="{{ $encrypted ? 'password' : 'text' }}"
                                                wire:model="values.{{ $fqcn }}.{{ $name }}"
                                                class="w-full max-w-2xl"
                                                :viewable="$encrypted"
                                            />
                                    @endswitch

                                    <flux:error name="values.{{ $fqcn }}.{{ $name }}" />

                                    @if (! empty($desc))
                                        <flux:text class="text-xs opacity-70">{{ $desc }}</flux:text>
                                    @endif
                                </flux:field>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <flux:button
                                wire:click="saveClass('{{ $classParam }}')"
                                wire:key="save-class-{{ md5($fqcn) }}"
                                wire:target="{{ $classTargets }}"
                                data-admin-settings-save-class-button
                                wire:dirty.remove.attr="disabled"
                                disabled
                            >
                                Änderungen speichern
                            </flux:button>
                        </div>
                    </flux:card>
                </div>
            </flux:tab.panel>
        @endforeach
    </flux:tab.group>

    <!-- Confirmation modal for saving settings -->
    <flux:modal name="admin-setting-confirm" class="min-w-104" :dismissible="false">
        @php
            $pc = $pendingClass ?? null;
            $pendingValuesMap = $pendingValues ?? [];
            $title = $pc && isset($classes[$pc]) ? ($classes[$pc]['title'] ?? class_basename($pc)) : null;
            $changedCount = count($pendingValuesMap);
        @endphp
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Einstellungen ändern?</flux:heading>
                <flux:text class="mt-2 space-y-2">
                    <p>Du bist dabei, die Einstellungen für <strong>{{ $title }}</strong> zu ändern.</p>
                    <p class="text-sm opacity-80">Dies kann Auswirkungen auf das Systemverhalten haben.</p>
                    <p class="text-xs opacity-70">{{ $changedCount }} {{ $changedCount === 1 ? 'Feld wird' : 'Felder werden' }} gespeichert.</p>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="cancelPending">Abbrechen</flux:button>
                <flux:button variant="danger" wire:click="commitPending">Änderungen speichern</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
