<div class="space-y-6">
    <flux:card class="space-y-4">
        <form wire:submit="storeFile" class="grid gap-4 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
            <flux:field>
                <flux:label>Zielordner</flux:label>
                <flux:input wire:model.blur="directory" placeholder="z.B. partners oder dokumente/2026" />
                <flux:error name="directory" />
            </flux:field>

            <flux:field>
                <flux:label>Datei</flux:label>
                <flux:input type="file" wire:model="file" />
                <flux:error name="file" />
            </flux:field>

            <flux:button type="submit" variant="primary" wire:target="storeFile,file" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="storeFile,file">Hochladen</span>
                <span wire:loading wire:target="storeFile,file">Lädt hoch...</span>
            </flux:button>
        </form>

        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
            Aktueller Ordner: <code>{{ $directory === '' ? '/' : $directory }}</code>
        </flux:text>
    </flux:card>

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">Dateien</flux:heading>

            @if ($directory !== '')
                <flux:button size="sm" variant="ghost" wire:click="openDirectory(@js(dirname($directory) === '.' ? '' : dirname($directory)))">
                    Eine Ebene höher
                </flux:button>
            @endif
        </div>

        @if ($directories !== [])
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($directories as $childDirectory)
                    <button type="button" wire:key="directory-{{ $childDirectory }}" wire:click="openDirectory(@js($childDirectory))" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 text-left hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        <flux:icon.folder class="size-5 text-zinc-500" />
                        <span class="truncate text-sm font-medium">{{ basename($childDirectory) }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        @if ($files === [])
            <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                <flux:text>Keine Dateien in diesem Ordner.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Datei</flux:table.column>
                    <flux:table.column>Grösse</flux:table.column>
                    <flux:table.column>Geändert</flux:table.column>
                    <flux:table.column class="text-right">Aktion</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($files as $fileItem)
                        <flux:table.row wire:key="file-{{ $fileItem['path'] }}">
                            <flux:table.cell>
                                <div class="min-w-0">
                                    <a href="{{ $fileItem['url'] }}" target="_blank" class="font-medium hover:underline">{{ $fileItem['name'] }}</a>
                                    <div class="truncate text-xs text-zinc-500">{{ $fileItem['path'] }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ \Illuminate\Support\Number::fileSize($fileItem['size']) }}</flux:table.cell>
                            <flux:table.cell>{{ date('d.m.Y H:i', $fileItem['last_modified']) }}</flux:table.cell>
                            <flux:table.cell class="text-right">
                                <flux:button size="xs" variant="danger" wire:click="confirmDelete(@js($fileItem['path']))">Löschen</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:modal name="delete-admin-file" class="min-w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Datei löschen?</flux:heading>
                <flux:text class="mt-2">{{ $pendingDeletePath }}</flux:text>
            </div>

            @if ($pendingDeleteReferences !== [])
                <flux:callout variant="warning">
                    <flux:callout.heading>Diese Datei wird noch verwendet</flux:callout.heading>
                    <flux:callout.text>
                        @foreach ($pendingDeleteReferences as $reference)
                            <div>{{ $reference['label'] }}</div>
                        @endforeach
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:error name="pendingDeletePath" />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="cancelDelete">Abbrechen</flux:button>
                <flux:button type="button" variant="danger" wire:click="deleteFile" :disabled="$pendingDeleteReferences !== []">Löschen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
