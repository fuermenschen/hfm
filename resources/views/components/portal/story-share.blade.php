@props([
    'registrationId',
    'shareId' => null,
    'athleteName' => null,
    'shareTexts' => [],
    'showTextTab' => false,
    'heading' => 'Deine Spendenaktion teilen',
    'description' => 'Nutze eine fertige Story oder kopiere einen persönlichen Text.',
])

@php($shareId ??= $registrationId)

<flux:modal name="share-story-{{ $shareId }}" class="space-y-6 sm:w-full md:w-xl">
    <div>
        <flux:heading size="lg">{{ $heading }}</flux:heading>
        <flux:text class="mt-1">{{ $description }}</flux:text>
    </div>

    @if ($showTextTab)
        <flux:tab.group>
            <flux:tabs variant="segmented">
                <flux:tab name="story" selected>Story</flux:tab>
                <flux:tab name="text">Text</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="story" class="pt-5">
                @include('components.portal.story-share-panel')
            </flux:tab.panel>

            <flux:tab.panel name="text" class="space-y-4 pt-5">
                <flux:text>Wähle eine Vorlage, teile sie direkt oder kopiere sie für WhatsApp, Instagram und andere Apps.</flux:text>

                <flux:tab.group>
                    <flux:tabs variant="segmented">
                        <flux:tab name="hochdeutsch" selected>Hochdeutsch</flux:tab>
                        <flux:tab name="schweizerdeutsch">Schweizerdeutsch</flux:tab>
                    </flux:tabs>

                    @foreach (['hochdeutsch', 'schweizerdeutsch'] as $language)
                        <flux:tab.panel :name="$language" class="space-y-4 pt-4">
                            @foreach ($shareTexts as $template)
                                @php($shareText = $template[$language])
                                <div data-share-text-template class="space-y-3">
                                    <flux:heading size="sm">{{ $shareText['title'] }}</flux:heading>
                                    <textarea data-share-text-content readonly class="min-h-48 w-full resize-none rounded-lg border border-zinc-300 bg-zinc-50 p-3 text-sm leading-6 dark:border-slate-700 dark:bg-slate-800">{{ $shareText['text'] }}</textarea>
                                    <div class="flex flex-wrap gap-3">
                                        <flux:button data-share-text variant="primary" icon="arrow-up-tray">Text teilen</flux:button>
                                        <flux:button data-copy-text variant="outline" icon="clipboard-document">Text kopieren</flux:button>
                                    </div>
                                    <flux:text data-share-text-status class="text-sm" role="status" aria-live="polite"></flux:text>
                                </div>
                            @endforeach
                        </flux:tab.panel>
                    @endforeach
                </flux:tab.group>
            </flux:tab.panel>
        </flux:tab.group>
    @else
        @include('components.portal.story-share-panel')
    @endif
</flux:modal>
