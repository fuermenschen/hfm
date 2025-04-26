<form wire:submit="submit"
      class="flex flex-col space-y-sm sm:grid-cols-2 sm:grid max-w-full sm:space-y-0 sm:gap-sm mt-sm">

    <flux:input label="Test" name="test" wire:model.live="test" placeholder="gib etwas ein" required
                class="sm:col-span-2" />
    <flux:heading>{{ $this->test }}</flux:heading>
    <flux:button
        type="submit"
        label="Absenden"
        variant="filled"
        icon="paper-airplane"
    >Senden
    </flux:button>
</form>
