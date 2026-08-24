<flux:button
    type="button"
    variant="primary"
    :loading="false"
    wire:click="confirm"
    wire:target="confirm"
    class="w-full data-loading:pointer-events-none data-loading:opacity-70 sm:w-auto"
>
    <span
        wire:loading.remove
        wire:target="confirm"
    >{{ $type === 'athlete' ? 'Anmeldung bestätigen' : 'Spende bestätigen' }}</span>
    <span wire:loading wire:target="confirm">Wird bestätigt …</span>
</flux:button>
