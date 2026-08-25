<div>
    @if ($action === 'create')
        <form wire:submit="submit" class="space-y-3">
            <flux:input wire:model="name" label="Gruppenname" required maxlength="255" />
            <flux:error name="name" />
            <flux:callout icon="exclamation-triangle" variant="warning" inline>
                <flux:callout.text>Der Name bleibt dauerhaft und kann nicht geändert werden.</flux:callout.text>
            </flux:callout>
            <flux:button type="submit" wire:target="submit" icon="user-group">
                <span wire:loading.remove wire:target="submit">Gruppe verbindlich gründen</span>
                <span wire:loading wire:target="submit">Wird gespeichert ...</span>
            </flux:button>
        </form>
    @else
        @php
            $groupName = $groupName ?? 'diese Gruppe';
            $targetName = $targetName ?? 'diese Person';
            $labels = ['request' => 'Beitritt anfragen', 'withdraw' => 'Anfrage zurückziehen', 'leave' => 'Gruppe verlassen', 'accept' => 'Annehmen', 'deny' => 'Ablehnen', 'remove' => 'Aus Gruppe entfernen', 'promote' => 'Zu Administrator:in machen', 'demote' => 'Admin-Rechte entfernen', 'delete' => 'Gruppe löschen'];
            $confirmations = ['request' => 'Deine Anfrage wird als offen gespeichert. Bis sie zurückgezogen oder abgelehnt wird, kannst du keiner anderen Gruppe beitreten.', 'withdraw' => 'Möchtest du deine Anfrage für die Gruppe "'.$groupName.'" zurückziehen?', 'leave' => 'Möchtest du die Gruppe "'.$groupName.'" verlassen?', 'accept' => 'Möchtest du '.$targetName.' als Mitglied in die Gruppe "'.$groupName.'" aufnehmen?', 'deny' => 'Möchtest du die Anfrage von '.$targetName.' für die Gruppe "'.$groupName.'" ablehnen?', 'remove' => 'Möchtest du '.$targetName.' aus der Gruppe "'.$groupName.'" entfernen?', 'promote' => 'Möchtest du '.$targetName.' zum/zur Administrator:in der Gruppe "'.$groupName.'" machen?', 'demote' => 'Möchtest du die Admin-Rechte von '.$targetName.' entfernen?', 'delete' => 'Möchtest du die Gruppe "'.$groupName.'" endgültig löschen?'];
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
