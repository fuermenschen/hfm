<div>
    @props(['first_name', 'last_name', 'email'])
    <flux:fieldset>
        <flux:input wire:model.live.debounce="first_name" label="Vorname" placeholder="Ursula"
                    value="{{ $first_name ?? '' }}" required />
        <flux:input wire:model.live.debounce="last_name" label="Nachname" placeholder="Hugentobler"
                    value="{{ $last_name ?? '' }}" required />
        <flux:input wire:model.live.debounce="email" label="E-Mail" placeholder="hugentobler.ursi@posteo.ch"
                    value="{{ $email ?? '' }}" required />
        <flux:button
            wire:click="submitRegistration"
            variant="filled"
            icon-leading="paper-airplane"
            class="mt-4"
            type="submit"
        >Anmelden
        </flux:button>
    </flux:fieldset>
</div>
