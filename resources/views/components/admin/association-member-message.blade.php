@props([
    'all_members'
])
<div>
    <flux:modal name="association-member-message-editor" class="w-full sm:size-8/12 lg:w-1/2 space-y-6"
                variant="flyout">
        <div>
            <flux:heading size="lg">Nachricht an Mitglied(er)</flux:heading>
            <flux:subheading>Die Nachricht wird dann per E-Mail versendet.</flux:subheading>
        </div>

        <flux:select
            variant="listbox"
            indicator="checkbox"
            multiple
            searchable
            placeholder="Wähle Empfänger:innen"
            selected-suffix="Empfänger:innen gewählt"
            clear="close"
            wire:model.live.debounce="selected_members">
            <x-slot name="search">
                <flux:select.search class="px-4" placeholder="Empfänger:innen suchen" />
            </x-slot>
            @foreach ($all_members as $member)
                <flux:option value="{{ $member->id }}">
                    {{ $member->first_name }} {{ $member->last_name }} ({{ $member->email }})
                </flux:option>
            @endforeach
        </flux:select>
        <flux:error name="selected_members" />


        <flux:input
            label="Betreff"
            placeholder="Betreff der Nachricht"
            wire:model.live.debounce="subject"
            required
            icon-trailing="envelope"
        />

        <flux:editor wire:model.live.debounce="message"
                     toolbar="bold italic underline | bullet ordered | link ~ placeholders" />
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
            wire:click="sendMessageRequest"
            icon-trailing="paper-airplane"
        >Nachricht senden
        </flux:button>

    </flux:modal>

    <flux:modal name="send-association-member-message-confirmation" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nachricht senden?</flux:heading>
                <flux:text class="mt-2"><p>
                        Die Nachricht wird an {{ count($selected_members) }} Mitglieder gesendet.
                    </p></flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Nei, doch nöd</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger" wire:click="sendMessage">Nachricht senden</flux:button>
            </div>
        </div>
    </flux:modal>


</div>
