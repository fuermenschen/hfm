<flux:button
    type="button"
    variant="primary"
    wire:click="confirm"
    wire:target="confirm"
    data-test="confirm-{{ $type }}"
>
    {{ $type === 'athlete' ? 'Anmeldung bestätigen' : 'Spende bestätigen' }}
</flux:button>
