<div
    id="share-story-{{ $shareId }}"
    data-story-share
    data-story-share-light-preview="{{ route('portal.story-image.preview', ['athleteRegistration' => $registrationId, 'variant' => 'light']) }}"
    data-story-share-dark-preview="{{ route('portal.story-image.preview', ['athleteRegistration' => $registrationId, 'variant' => 'dark']) }}"
    data-story-share-light-download="{{ route('portal.story-image.download', ['athleteRegistration' => $registrationId, 'variant' => 'light']) }}"
    data-story-share-dark-download="{{ route('portal.story-image.download', ['athleteRegistration' => $registrationId, 'variant' => 'dark']) }}"
>
    <div class="grid grid-cols-2 gap-3">
        @foreach (['light' => 'Hell', 'dark' => 'Dunkel'] as $variant => $label)
            <button data-story-variant="{{ $variant }}" type="button" class="overflow-hidden rounded-xl border-2 border-transparent text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hfm-red data-[selected=true]:border-hfm-red">
                <div class="relative aspect-[9/16]">
                    <div data-story-preview-skeleton="{{ $variant }}" class="absolute inset-0 rounded-lg bg-zinc-200 transition-opacity duration-300 ease-out motion-reduce:transition-none dark:bg-zinc-700">
                        <div class="flex size-full flex-col items-center justify-center gap-2 text-zinc-500 dark:text-zinc-300">
                            <flux:icon.loading class="size-7 motion-safe:animate-spin" />
                            <span class="text-center text-xs font-medium">Bild wird vorbereitet</span>
                        </div>
                    </div>
                    <img data-story-preview="{{ $variant }}" alt="{{ $label }} Vorschau{{ $athleteName ? ' für '.$athleteName : ' deiner Story' }}" class="absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-300 ease-out motion-reduce:transition-none" />
                </div>
                <span class="block p-2 text-sm font-medium">{{ $label }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-5 flex flex-wrap gap-3">
        <flux:button data-share-story variant="outline" icon="arrow-up-tray">Story teilen</flux:button>
        <flux:button data-download-story variant="outline" icon="arrow-down-tray">Story-Bild herunterladen</flux:button>
    </div>
    <flux:text data-story-share-status class="mt-3" role="status" aria-live="polite"></flux:text>
</div>
