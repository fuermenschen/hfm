<div class="space-y-8">

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
        @foreach ($classes as $fqcn => $meta)
            @php
                $short = class_basename($fqcn);
                $classTitle = $meta['title'] ?? $short;
                $classDesc = $meta['description'] ?? null;
            @endphp

        <flux:card>
            <div class="mb-6">
                <flux:heading size="lg">{{ $classTitle }}</flux:heading>
                @if (!empty($classDesc))
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
                        $rules = $info['rules'] ?? null;
                        $isRequired = false;
                        if (is_string($rules)) { $isRequired = str_contains($rules, 'required'); }
                        elseif (is_array($rules)) { $isRequired = in_array('required', $rules, true); }
                        $wKey = md5($fqcn.'|'.$name);
                        $classParam = addslashes($fqcn);
                    @endphp

                    <flux:field>
                        <span>
                            <flux:label>{{ $title }}</flux:label>
                            <span wire:dirty wire:target="values.{{ $fqcn }}.{{ $name }}" class="ml-1 text-xs opacity-70">(ungespeichert)</span>
                        </span>

                        <flux:input.group>
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
                                            wire:keyup.enter="saveSingle('{{ $classParam }}','{{ $name }}')"
                                            class="w-full max-w-md"
                                        />
                                    @else
                                        <flux:input type="number"
                                            wire:model="values.{{ $fqcn }}.{{ $name }}"
                                            wire:keyup.enter="saveSingle('{{ $classParam }}','{{ $name }}')"
                                            step="{{ in_array($type, ['float','double']) ? '0.01' : '1' }}"
                                            class="w-full max-w-md"
                                        />
                                    @endif
                                    @break

                                @case('array')
                                    <flux:textarea
                                        wire:model="values.{{ $fqcn }}.{{ $name }}"
                                        wire:keyup.enter="saveSingle('{{ $classParam }}','{{ $name }}')"
                                        placeholder="JSON oder komma-getrennte Werte"
                                        class="w-full max-w-2xl"
                                    />
                                    @break

                                @default
                                    <flux:input
                                        type="{{ $encrypted ? 'password' : 'text' }}"
                                        wire:model="values.{{ $fqcn }}.{{ $name }}"
                                        wire:keyup.enter="saveSingle('{{ $classParam }}','{{ $name }}')"
                                        class="w-full max-w-2xl"
                                        :viewable="$encrypted"
                                    />
                            @endswitch

                            <flux:button class="shrink-0"
                                wire:click="saveSingle('{{ $classParam }}','{{ $name }}')"
                                wire:key="save-{{ $wKey }}"
                            >
                                Speichern
                            </flux:button>
                        </flux:input.group>

                        <flux:error name="values.{{ $fqcn }}.{{ $name }}" />

                        @if (!empty($desc))
                            <flux:text class="text-xs opacity-70">{{ $desc }}</flux:text>
                        @endif
                    </flux:field>
                @endforeach
            </div>
        </flux:card>

    @endforeach
    </div>

    <!-- Confirmation modal for saving a setting -->
    <flux:modal name="admin-setting-confirm" class="min-w-104" :dismissible="false">
        @php
            $pc = $pendingClass ?? null;
            $pn = $pendingName ?? null;
            $meta = $pc && isset($classes[$pc]['settings'][$pn]) ? $classes[$pc]['settings'][$pn] : null;
            $title = $meta['title'] ?? $pn;
            $current = $pc && $pn ? ($classes[$pc]['settings'][$pn]['value'] ?? null) : null;
        @endphp
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Einstellung ändern?</flux:heading>
                <flux:text class="mt-2 space-y-2">
                    <p>Du bist dabei, die Einstellung <strong>{{ $title }}</strong> zu ändern.</p>
                    <p class="text-sm opacity-80">Dies kann Auswirkungen auf das Systemverhalten haben.</p>
                    <div class="mt-2 text-xs opacity-70 space-y-1">
                        <div><span class="opacity-60">Aktueller Wert:</span> <span class="font-mono">{{ is_array($current) ? json_encode($current) : (is_bool($current) ? ($current ? 'true' : 'false') : (string)($current ?? '—')) }}</span></div>
                        <div><span class="opacity-60">Neuer Wert:</span> <span class="font-mono">{{ is_array($pendingValue) ? json_encode($pendingValue) : (is_bool($pendingValue) ? ($pendingValue ? 'true' : 'false') : (string)($pendingValue ?? '—')) }}</span></div>
                    </div>
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="cancelPending">Abbrechen</flux:button>
                <flux:button variant="danger" wire:click="commitPending">{{ $title }} ändern</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
