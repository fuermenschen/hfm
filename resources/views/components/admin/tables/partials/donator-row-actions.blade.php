@props(['row', 'actionGroups' => null])

@php
    $resolvedActionGroups = is_array($actionGroups)
        ? $actionGroups
        : \App\Support\Datatable\Actions\DonorRowActionFactory::make($row);
@endphp

<div class="flex items-center justify-center">
    <flux:dropdown>
        <flux:button variant="subtle" size="xs" icon="ellipsis-horizontal" />
        <flux:menu>
            @foreach ($resolvedActionGroups as $groupLabel => $actions)
                <flux:menu.group heading="{{ $groupLabel }}">
                    @foreach ($actions as $action)
                        @php($type = (string) ($action['type'] ?? 'static'))
                        @php($icon = $action['icon'] ?? null)
                        @php($variant = $action['variant'] ?? null)
                        @php($isDisabled = (bool) ($action['disabled'] ?? false))

                        @if ($type === 'wire')
                            @if (is_string($variant) && $variant !== '')
                                <flux:menu.item icon="{{ $icon }}" variant="{{ $variant }}" wire:click="{{ $action['click'] }}" :disabled="$isDisabled">
                                    {{ $action['label'] }}
                                </flux:menu.item>
                            @else
                                <flux:menu.item icon="{{ $icon }}" wire:click="{{ $action['click'] }}" :disabled="$isDisabled">
                                    {{ $action['label'] }}
                                </flux:menu.item>
                            @endif
                        @elseif ($type === 'href')
                            <flux:menu.item icon="{{ $icon }}" :href="$action['href']" target="{{ $action['target'] ?? '_self' }}" :disabled="$isDisabled">
                                {{ $action['label'] }}
                            </flux:menu.item>
                        @else
                            <flux:menu.item disabled="true">{{ $action['label'] }}</flux:menu.item>
                        @endif
                    @endforeach
                </flux:menu.group>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>
