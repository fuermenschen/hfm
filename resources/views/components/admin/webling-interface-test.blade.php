<div class="space-y-6">

    {{-- Progress bar --}}
    @if ($step !== 'error')
        <flux:field>
            <flux:label>
                @if ($step === 'intro') Bereit zum Starten
                @elseif ($step === 'running') Teste Webling-Schnittstelle…
                @elseif ($step === 'inspect_pdf') PDF prüfen
                @elseif ($step === 'inspect_link') Direktlink prüfen
                @elseif ($step === 'cleanup') Aufräumen…
                @elseif ($step === 'done') Test abgeschlossen
                @endif
            </flux:label>
            <flux:progress value="{{ $progress }}" />
            <flux:description>
                @if ($step === 'intro') Schritt 1 von 4: Test vorbereiten
                @elseif ($step === 'running') Schritt 1 von 4: Debitor und PDF werden erstellt…
                @elseif ($step === 'inspect_pdf') Schritt 2 von 4: PDF herunterladen und prüfen
                @elseif ($step === 'inspect_link') Schritt 3 von 4: Direktlink in Webling prüfen
                @elseif ($step === 'cleanup') Schritt 4 von 4: Testdaten werden gelöscht…
                @elseif ($step === 'done') Test erfolgreich abgeschlossen
                @endif
            </flux:description>
        </flux:field>

        <flux:separator />
    @endif

    {{-- ============================================================ --}}
    {{-- STEP: intro                                                   --}}
    {{-- ============================================================ --}}
    @if ($step === 'intro')
        <div class="space-y-4">
            <flux:callout icon="information-circle">
                <flux:callout.heading>Was wird getestet?</flux:callout.heading>
                <flux:callout.text>
                    Der Test erstellt einen echten Debitor und Brief/PDF in Webling mit zufälligen Testdaten,
                    ermöglicht dir die manuelle Prüfung des PDFs und des Direktlinks, und löscht anschliessend
                    alle Testdaten automatisch wieder aus Webling.
                </flux:callout.text>
            </flux:callout>

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-2">
                <flux:heading size="sm">Testdaten (zufällig generiert)</flux:heading>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">Name</span>
                    <span class="font-medium">{{ $testData['first_name'] }} {{ $testData['last_name'] }}</span>

                    <span class="text-zinc-500 dark:text-zinc-400">Adresse</span>
                    <span class="font-medium">{{ $testData['address'] }}</span>

                    <span class="text-zinc-500 dark:text-zinc-400">PLZ / Ort</span>
                    <span class="font-medium">{{ $testData['zip'] }} {{ $testData['city'] }}</span>

                    <span class="text-zinc-500 dark:text-zinc-400">Betrag</span>
                    <span class="font-medium">Fr. {{ number_format($testData['amount'], 2, '.', '\'') }}</span>

                    <span class="text-zinc-500 dark:text-zinc-400">Sportart</span>
                    <span class="font-medium">{{ $testData['sport'] }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <flux:button wire:click="restartWizard" variant="ghost" icon="arrow-path">Neue Daten</flux:button>
            <flux:button wire:click="start" wire:loading.attr="disabled" wire:target="start" variant="primary" icon="play">Test starten</flux:button>
        </div>

    {{-- ============================================================ --}}
    {{-- STEP: running                                                 --}}
    {{-- ============================================================ --}}
    @elseif ($step === 'running')
        <div class="flex flex-col items-center gap-4 py-4">
            <flux:icon.arrow-path class="size-8 animate-spin text-accent" />
            <div class="text-center space-y-1">
                <flux:heading size="sm">Schnittstelle wird getestet…</flux:heading>
                <flux:text class="text-sm text-zinc-500">Debitor und Brief/PDF werden in Webling erstellt. Bitte warten.</flux:text>
            </div>
        </div>

    {{-- ============================================================ --}}
    {{-- STEP: inspect_pdf                                             --}}
    {{-- ============================================================ --}}
    @elseif ($step === 'inspect_pdf')
        <div class="space-y-4">
            <flux:callout icon="check-circle" color="green">
                <flux:callout.heading>Debitor und PDF erfolgreich erstellt</flux:callout.heading>
                <flux:callout.text>
                    Debitor-ID: <strong>{{ $debitorId }}</strong>
                    @if ($tempPdfSize)
                        &nbsp;·&nbsp; PDF-Grösse: {{ number_format($tempPdfSize / 1024, 1) }} KB
                    @endif
                </flux:callout.text>
            </flux:callout>

            <div class="space-y-3">
                <flux:heading size="sm">1. PDF herunterladen und prüfen</flux:heading>
                <flux:button wire:click="downloadPdf" icon="arrow-down-tray" variant="filled">PDF herunterladen</flux:button>
            </div>

            <flux:separator />

            <div class="space-y-3">
                <flux:heading size="sm">2. Checkliste ausfüllen</flux:heading>
                <div class="space-y-2">
                    @foreach ($checklistLabels as $key => $label)
                        <flux:checkbox
                            wire:model.live="checklist.{{ $key }}"
                            :label="$label"
                        />
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:button
                wire:click="confirmPdf"
                variant="primary"
                icon="arrow-right"
                :disabled="! $checklistComplete"
            >
                Weiter: Direktlink prüfen
            </flux:button>
        </div>

        @if (! $checklistComplete)
            <flux:text class="text-xs text-zinc-400 text-right">Bitte alle Punkte der Checkliste abhaken, um fortzufahren.</flux:text>
        @endif

    {{-- ============================================================ --}}
    {{-- STEP: inspect_link                                            --}}
    {{-- ============================================================ --}}
    @elseif ($step === 'inspect_link')
        <div class="space-y-4">
            <flux:callout icon="check-circle" color="green">
                <flux:callout.heading>PDF-Prüfung bestanden</flux:callout.heading>
                <flux:callout.text>Alle Punkte der Checkliste wurden abgehakt.</flux:callout.text>
            </flux:callout>

            <div class="space-y-3">
                <flux:heading size="sm">Direktlink in Webling prüfen</flux:heading>
                <flux:text class="text-sm">
                    Öffne den folgenden Link und verifiziere, dass der Debitor korrekt in Webling erscheint.
                </flux:text>

                @if ($debitorUrl)
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ $debitorUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 text-sm text-accent underline underline-offset-2 hover:opacity-80 break-all"
                        >
                            <flux:icon.arrow-top-right-on-square class="size-4 shrink-0" />
                            {{ $debitorUrl }}
                        </a>
                    </div>
                @endif

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-sm space-y-1">
                    <div class="font-medium text-zinc-600 dark:text-zinc-300">Was du prüfen solltest:</div>
                    <ul class="list-disc list-inside text-zinc-500 dark:text-zinc-400 space-y-0.5">
                        <li>Debitor mit dem Namen <strong>{{ $testData['first_name'] }} {{ $testData['last_name'] }}</strong> erscheint</li>
                        <li>Betrag Fr. {{ number_format($testData['amount'], 2, '.', '\'') }} ist korrekt</li>
                        <li>Rechnung hat den Status «Offen» oder «Gesendet»</li>
                        <li>Brief / PDF ist dem Debitor zugeordnet</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:button wire:click="confirmLink" wire:loading.attr="disabled" wire:target="confirmLink" variant="danger" icon="trash">Aufräumen &amp; Test beenden</flux:button>
        </div>

    {{-- ============================================================ --}}
    {{-- STEP: cleanup                                                 --}}
    {{-- ============================================================ --}}
    @elseif ($step === 'cleanup')
        <div class="flex flex-col items-center gap-4 py-4">
            <flux:icon.arrow-path class="size-8 animate-spin text-accent" />
            <div class="text-center space-y-1">
                <flux:heading size="sm">Testdaten werden gelöscht…</flux:heading>
                <flux:text class="text-sm text-zinc-500">Debitor wird in Webling gelöscht und temporäre Dateien werden bereinigt.</flux:text>
            </div>
        </div>

    {{-- ============================================================ --}}
    {{-- STEP: done                                                    --}}
    {{-- ============================================================ --}}
    @elseif ($step === 'done')
        <div class="space-y-4">
            <flux:callout icon="check-circle" color="green">
                <flux:callout.heading>Webling-Schnittstelle funktioniert korrekt</flux:callout.heading>
                <flux:callout.text>
                    Der vollständige Test wurde erfolgreich abgeschlossen. Debitor und temporäre Dateien wurden bereinigt.
                </flux:callout.text>
            </flux:callout>

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 space-y-2 text-sm">
                <div class="font-medium text-zinc-600 dark:text-zinc-300">Testprotokoll</div>
                <div class="space-y-1 text-zinc-500 dark:text-zinc-400">
                    <div class="flex items-center gap-2"><flux:icon.check-circle class="size-4 text-green-500 shrink-0" /> Debitor in Webling erstellt</div>
                    <div class="flex items-center gap-2"><flux:icon.check-circle class="size-4 text-green-500 shrink-0" /> Brief/PDF von Webling generiert und heruntergeladen</div>
                    <div class="flex items-center gap-2"><flux:icon.check-circle class="size-4 text-green-500 shrink-0" /> PDF manuell geprüft (alle Checklistenpunkte bestanden)</div>
                    <div class="flex items-center gap-2"><flux:icon.check-circle class="size-4 text-green-500 shrink-0" /> Direktlink in Webling manuell geprüft</div>
                    <div class="flex items-center gap-2"><flux:icon.check-circle class="size-4 text-green-500 shrink-0" /> Testdaten vollständig bereinigt</div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:button wire:click="restartWizard" icon="arrow-path">Neuen Test starten</flux:button>
            <flux:button variant="primary" x-on:click="$flux.modal('webling-interface-test').close()">Schliessen</flux:button>
        </div>

    {{-- ============================================================ --}}
    {{-- STEP: error                                                   --}}
    {{-- ============================================================ --}}
    @elseif ($step === 'error')
        <div class="space-y-4">
            <flux:callout icon="exclamation-triangle" color="red">
                <flux:callout.heading>Fehler beim Test</flux:callout.heading>
                <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
            </flux:callout>

            @if ($debitorId)
                <flux:callout icon="exclamation-circle" color="yellow">
                    <flux:callout.heading>Testdaten müssen noch bereinigt werden</flux:callout.heading>
                    <flux:callout.text>
                        Der Test-Debitor (ID: <strong>{{ $debitorId }}</strong>) existiert noch in Webling.
                        Du kannst versuchen, ihn über den Button unten zu löschen, oder ihn manuell entfernen.
                        @if ($debitorUrl)
                            <br><a href="{{ $debitorUrl }}" target="_blank" rel="noopener noreferrer" class="underline">Direktlink in Webling öffnen</a>
                        @endif
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:button wire:click="restartWizard" variant="ghost" icon="arrow-path">Neu starten</flux:button>
            @if ($debitorId)
                <flux:button wire:click="runCleanup" variant="danger" icon="trash">Trotzdem aufräumen</flux:button>
            @endif
        </div>
    @endif

</div>
