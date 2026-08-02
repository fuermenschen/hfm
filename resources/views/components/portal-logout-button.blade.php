<flux:button
    type="button"
    variant="ghost"
    :loading="false"
    icon="arrow-right-start-on-rectangle"
    class="w-full justify-start"
    wire:click="logout"
    wire:target="logout"
>
    <span wire:loading.remove wire:target="logout">Abmelden</span>
    <span wire:loading wire:target="logout">Wird abgemeldet …</span>
</flux:button>
