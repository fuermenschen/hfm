<div class="space-y-8" data-admin-settings-root>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
        @foreach ($classes as $fqcn => $meta)
            @php
                $short = class_basename($fqcn);
                $classTitle = $meta['title'] ?? $short;
                $classDesc = $meta['description'] ?? null;
                $classTargets = collect(array_keys($meta['settings'] ?? []))
                    ->map(fn (string $name) => "values.$fqcn.$name")
                    ->implode(',');
                $classParam = addslashes($fqcn);
            @endphp

        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">{{ $classTitle }}</flux:heading>
                @if (!empty($classDesc))
                    <flux:subheading>{{ $classDesc }}</flux:subheading>
                @endif
                <flux:subheading class="text-xs opacity-60">{{ $fqcn }}</flux:subheading>
                <flux:text wire:dirty wire:target="{{ $classTargets }}" class="mt-2 text-xs text-accent">Ungespeicherte Änderungen</flux:text>
            </div>

            <div class="space-y-6">
                @foreach ($meta['settings'] as $name => $info)
                    @php
                        $type = $info['type'] ?? null;
                        $desc = $info['description'] ?? null;
                        $title = $info['title'] ?? $name;
                        $encrypted = $info['encrypted'] ?? false;
                    @endphp

                    <flux:field>
                        <span>
                            <flux:label>{{ $title }}</flux:label>
                            <span wire:dirty wire:target="values.{{ $fqcn }}.{{ $name }}" class="ml-1 text-xs opacity-70 text-accent">(ungespeichert)</span>
                        </span>

                        @switch($type)
                            @case('bool')
                                <flux:switch
                                    wire:model="values.{{ $fqcn }}.{{ $name }}"
                                />
                                @break

                            @case('int')
                            @case('float')
                            @case('double')
                                @if ($encrypted)
                                    <flux:input type="password"
                                        viewable
                                        wire:model="values.{{ $fqcn }}.{{ $name }}"
                                        class="w-full max-w-md"
                                    />
                                @else
                                    <flux:input type="number"
                                        wire:model="values.{{ $fqcn }}.{{ $name }}"
                                        step="{{ in_array($type, ['float','double']) ? '0.01' : '1' }}"
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

                        @if (!empty($desc))
                            <flux:text class="text-xs opacity-70">{{ $desc }}</flux:text>
                        @endif
                    </flux:field>
                @endforeach

                <div class="flex items-center gap-2 pt-2">
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
            </div>
        </flux:card>

    @endforeach
    </div>

    <!-- Confirmation modal for saving a setting -->
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
