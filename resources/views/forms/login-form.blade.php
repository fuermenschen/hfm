<div
    wire:key="login-link-{{ $loginLinkState }}-{{ $sentAt ?? 0 }}"
    x-data="{
        resendIn: {{ $resendAvailableIn }},
        timer: null,
        init() {
            @if ($loginLinkState === 'sent')
                this.timer = setInterval(() => { if (this.resendIn > 0) this.resendIn-- }, 1000)
            @endif
        },
        destroy() { clearInterval(this.timer) },
    }"
>
    @if ($loginLinkState === 'form')
        <form wire:submit="save"
              class="flex flex-col w-96 max-w-full space-y-6 mt-6 sm:mx-auto items-stretch">

            @csrf

            <x-honeypot livewire-model="extraFields" />

            <flux:input
                icon-trailing="envelope"
                label="E-Mail"
                placeholder="francesca.arslan@posteo.ch"
                wire:model.blur="email"
                type="email"
                autocomplete="email"
            />

            <span class="sm:col-span-2">
                <flux:button type="submit" icon="paper-airplane">Login-Link erhalten</flux:button>
            </span>
        </form>
    @else
        <div class="flex flex-col w-96 max-w-full space-y-6 mt-6 sm:mx-auto items-stretch text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-hfm-red/10 text-hfm-red dark:bg-hfm-lightred/10 dark:text-hfm-lightred">
                <flux:icon.envelope class="size-8" />
            </div>

            <div>
                <flux:heading size="lg">Login-Link verschickt</flux:heading>
                <flux:text class="mt-2">
                    Wir haben an <strong>{{ $maskedEmail }}</strong> einen Login-Link geschickt. Bitte überprüfe dein Postfach.
                </flux:text>
                <flux:text class="mt-1 text-sm">
                    Der Link ist 15 Minuten gültig.
                </flux:text>
            </div>

            <div class="flex flex-col gap-3">
                <flux:button
                    wire:click="resend"
                    icon="paper-airplane"
                    x-bind:disabled="resendIn > 0"
                >
                    <span x-show="resendIn <= 0">Erneut senden</span>
                    <span x-show="resendIn > 0" x-text="'Erneut senden (in ' + resendIn + 's)'"></span>
                </flux:button>

                <flux:button variant="ghost" wire:click="changeEmail">Andere E-Mail-Adresse verwenden</flux:button>
            </div>
        </div>
    @endif
</div>
