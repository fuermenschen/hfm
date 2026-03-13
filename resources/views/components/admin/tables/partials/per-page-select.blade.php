@props(['options' => [10, 25, 50, 100, 200]])

<div class="flex items-center gap-2">
    <flux:text>Pro Seite</flux:text>
    <flux:select wire:model.live="perPage" class="w-24">
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </flux:select>
</div>
