<div>
    <flux:modal name="association-member-message-editor" class="w-full sm:size-8/12 lg:w-1/2 space-y-6"
                variant="flyout">
        <div>
            <flux:heading size="lg">Nachricht an {{ $this->member->first_name ?? "Ursula" }}</flux:heading>
            <flux:subheading>Die Nachricht wird dann per E-Mail an {{ $this->member->first_name ?? "Ursula" }}
                versendet.
            </flux:subheading>
        </div>

        <flux:input
            label="Betreff"
            placeholder="Betreff der Nachricht"
            wire:model.live.debounce="subject"
            required
            icon-trailing="envelope"
        />

        <flux:editor wire:model.live.debounce="message"
                     toolbar="bold italic underline | bullet ordered | link" />
        <flux:error name="message" />

        @if($attachments)
            <flux:heading>Anhänge</flux:heading>
        @endif

        @foreach($attachments as $attachment)
            <div class="flex items-center justify-between">
                <flux:subheading>{{ $attachment['name'] }}</flux:subheading>
                <flux:button type="button" size="xs" wire:click="removeAttachment('{{ $attachment['name'] }}')"
                             icon="trash">
                </flux:button>
            </div>
        @endforeach

        <div class="flex items-center justify-between">
            <flux:input type="file" wire:model="currentAttachment" label="Anhang hinzufügen" />

            <flux:icon.loading wire:loading wire:target="currentAttachment" />
        </div>

        <flux:button
            wire:click="sendMessage"
            icon-trailing="paper-airplane"
        >Nachricht senden
        </flux:button>

    </flux:modal>
</div>
