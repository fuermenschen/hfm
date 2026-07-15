@php
    $files = $files();
    $errorName = $attributes->wire('model')->value();
    $selectedUrl = $preview ? $selectedUrl() : null;
@endphp

<flux:field>
    @if ($label !== null)
        <flux:label>{{ $label }}</flux:label>
    @endif

    <flux:select {{ $attributes->whereStartsWith('wire:model') }} variant="listbox" searchable clearable :placeholder="$placeholder">
        <flux:select.option value="">{{ $placeholder }}</flux:select.option>

        @foreach ($files as $file)
            <flux:select.option value="{{ $valueFor($file['path']) }}">{{ $valueFor($file['path']) }}</flux:select.option>
        @endforeach
    </flux:select>

    @if ($selectedUrl !== null)
        <flux:text class="text-xs">
            <a href="{{ $selectedUrl }}" target="_blank" class="text-accent hover:underline">Ausgewählte Datei öffnen</a>
        </flux:text>
    @endif

    @if ($files === [])
        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Keine Dateien gefunden.</flux:text>
    @endif

    @if ($errorName)
        <flux:error :name="$errorName" />
    @endif
</flux:field>
