<div>
    @if ($action === 'create')
        <form wire:submit="submit" class="space-y-3">
            <flux:input wire:model="name" label="Gruppenname" required maxlength="255" />
            <flux:error name="name" />
            <flux:callout icon="exclamation-triangle" variant="warning" inline>
                <flux:callout.text>Der Name bleibt dauerhaft und kann nicht geändert werden.</flux:callout.text>
            </flux:callout>
            <flux:button type="submit" wire:target="submit" icon="user-group">Gruppe verbindlich gründen</flux:button>
        </form>
    @else
        @php
            $labels = ['request' => 'Beitritt anfragen', 'withdraw' => 'Anfrage zurückziehen', 'leave' => 'Gruppe verlassen', 'accept' => 'Annehmen', 'deny' => 'Ablehnen', 'remove' => 'Entfernen', 'promote' => 'Zu Administrator:in machen', 'demote' => 'Administratorrechte entfernen', 'delete' => 'Gruppe löschen'];
            $confirmations = ['request' => 'Deine Anfrage wird als offen gespeichert. Bis sie zurückgezogen oder abgelehnt wird, kannst du keiner anderen Gruppe beitreten.', 'withdraw' => 'Möchtest du diese Beitrittsanfrage zurückziehen?', 'leave' => 'Möchtest du diese Gruppe wirklich verlassen?', 'accept' => 'Möchtest du diese Person als Mitglied aufnehmen?', 'deny' => 'Möchtest du diese Beitrittsanfrage ablehnen?', 'remove' => 'Möchtest du dieses Mitglied aus der Gruppe entfernen?', 'promote' => 'Möchtest du diesem Mitglied Administratorrechte geben?', 'demote' => 'Möchtest du dieser Person die Administratorrechte entziehen?', 'delete' => 'Möchtest du diese Gruppe endgültig löschen?'];
        @endphp
        <flux:button
            wire:click="confirm"
            :variant="in_array($action, ['deny', 'remove', 'delete'], true) ? 'danger' : 'outline'"
            size="sm"
        >
            {{ $labels[$action] }}
        </flux:button>
        <flux:modal
            name="group-confirm-{{ $this->getId() }}"
            wire:model.self="confirming"
            :dismissible="false"
            class="space-y-6 sm:w-full md:w-xl"
        >
            <div class="space-y-2">
                <flux:heading size="lg">{{ $labels[$action] }}?</flux:heading>
                <flux:text>{{ $confirmations[$action] }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="cancel" variant="ghost">Abbrechen</flux:button>
                <flux:button
                    wire:click="submit"
                    wire:target="submit"
                    :variant="in_array($action, ['deny', 'remove', 'delete'], true) ? 'danger' : 'primary'"
                >
                    <span wire:loading.remove wire:target="submit">{{ $labels[$action] }}</span>
                    <span wire:loading wire:target="submit">Wird gespeichert ...</span>
                </flux:button>
            </div>
        </flux:modal>
    @endif
</div>
