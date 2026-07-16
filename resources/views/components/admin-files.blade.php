<div class="space-y-6">
    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:breadcrumbs>
                @foreach ($breadcrumbs as $breadcrumb)
                    @if ($breadcrumb['current'])
                        <flux:breadcrumbs.item>{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
                    @else
                        <flux:breadcrumbs.item href="#" wire:click.prevent="openDirectory(@js($breadcrumb['path']))">{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
                    @endif
                @endforeach
            </flux:breadcrumbs>

            <div class="flex flex-wrap gap-2">
                <flux:modal.trigger name="create-admin-folder">
                    <flux:button size="sm" icon="folder-plus">Neuer Ordner</flux:button>
                </flux:modal.trigger>

                <flux:modal.trigger name="upload-admin-file">
                    <flux:button size="sm" icon="arrow-up-tray" variant="primary">Datei hochladen</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <div
            x-data="{
                dragging: false,
                uploading: false,
                uploadFile(file) {
                    if (! file) return

                    this.uploading = true
                    $wire.upload('file', file, () => {
                        $wire.storeFile()
                        this.uploading = false
                    }, () => {
                        this.uploading = false
                    })
                },
            }"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false; uploadFile($event.dataTransfer.files[0])"
            class="space-y-2 rounded-lg transition-colors"
            x-bind:class="dragging ? 'bg-blue-50 dark:bg-blue-950/30' : ''"
        >
            <div x-show="dragging" x-cloak class="flex items-center gap-2 rounded-lg bg-blue-50 px-4 py-2 text-sm text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                <flux:icon.cloud-arrow-up class="size-4" />
                Datei hier ablegen zum Hochladen nach <span class="font-medium">{{ $directory === '' ? '/' : $directory }}</span>
            </div>

            <div x-show="uploading" x-cloak class="flex items-center gap-2 rounded-lg bg-zinc-50 px-4 py-2 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                <flux:icon.arrow-path class="size-4 animate-spin" />
                Datei wird hochgeladen...
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Datei</flux:table.column>
                    <flux:table.column>Grösse</flux:table.column>
                    <flux:table.column>Geändert</flux:table.column>
                    <flux:table.column class="text-right">Aktion</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @if ($directory !== '')
                        <flux:table.row wire:key="directory-parent">
                            <flux:table.cell>
                                <button type="button" wire:click="openParentDirectory" class="flex items-center gap-3 font-medium hover:underline">
                                    <flux:icon.arrow-up class="size-5 text-zinc-500" />
                                    ..
                                </button>
                            </flux:table.cell>
                            <flux:table.cell></flux:table.cell>
                            <flux:table.cell></flux:table.cell>
                            <flux:table.cell></flux:table.cell>
                        </flux:table.row>
                    @endif

                    @foreach ($directories as $childDirectory)
                        <flux:table.row wire:key="directory-{{ $childDirectory }}">
                            <flux:table.cell>
                                <button type="button" wire:click="openDirectory(@js($childDirectory))" class="flex items-center gap-3 font-medium hover:underline">
                                    <flux:icon.folder class="size-5 text-zinc-500" />
                                    {{ basename($childDirectory) }}
                                </button>
                            </flux:table.cell>
                            <flux:table.cell></flux:table.cell>
                            <flux:table.cell></flux:table.cell>
                            <flux:table.cell></flux:table.cell>
                        </flux:table.row>
                    @endforeach

                    @foreach ($files as $fileItem)
                        <flux:table.row wire:key="file-{{ $fileItem['path'] }}">
                            <flux:table.cell>
                                <div class="flex min-w-0 items-center gap-3">
                                    <flux:icon.document class="size-5 text-zinc-500" />
                                    <div class="min-w-0">
                                        <a href="{{ $fileItem['url'] }}" target="_blank" class="font-medium hover:underline">{{ $fileItem['name'] }}</a>
                                        <div class="truncate text-xs text-zinc-500">{{ $fileItem['path'] }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ \Illuminate\Support\Number::fileSize($fileItem['size']) }}</flux:table.cell>
                            <flux:table.cell>{{ date('d.m.Y H:i', $fileItem['last_modified']) }}</flux:table.cell>
                            <flux:table.cell class="py-0 text-right">
                                <flux:button size="xs" variant="danger" wire:click="confirmDelete(@js($fileItem['path']))">Löschen</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach

                    @if ($directories === [] && $files === [])
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center">
                                <div class="flex flex-col items-center gap-2 py-8 text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.cloud-arrow-up class="size-8" />
                                    <div>Dateien hierher ziehen oder über „Datei hochladen“ auswählen.</div>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endif
                </flux:table.rows>
            </flux:table>
        </div>

        <flux:error name="file" />
    </flux:card>

    <flux:modal name="create-admin-folder" class="min-w-96">
        <form wire:submit="createFolder" class="space-y-6">
            <div>
                <flux:heading size="lg">Ordner erstellen</flux:heading>
                <flux:text class="mt-2">Erstellt einen Ordner in {{ $directory === '' ? '/' : $directory }}.</flux:text>
            </div>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="newFolderName" placeholder="z.B. dokumente" autofocus />
                <flux:error name="newFolderName" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Erstellen</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="upload-admin-file" class="min-w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Datei hochladen</flux:heading>
                <flux:text class="mt-2">Zielordner: {{ $directory === '' ? '/' : $directory }}</flux:text>
            </div>

            <flux:file-upload wire:model="file" label="Datei">
                <flux:file-upload.dropzone heading="Datei hier ablegen oder klicken" text="Maximal 1 GB" with-progress />
            </flux:file-upload>

            <flux:error name="file" />
        </div>
    </flux:modal>

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
