<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('renders files relative to the selected directory', function (): void {
    Storage::disk('public')->put('partners/logo.svg', 'svg');

    $html = Blade::render('<x-admin.file-select directory="partners" wire:model="filePath" />');

    expect($html)->toContain('value="logo.svg"')
        ->and($html)->toContain('wire:model="filePath"');
});

it('filters files by extensions', function (): void {
    Storage::disk('public')->put('assets/logo.svg', 'svg');
    Storage::disk('public')->put('assets/readme.txt', 'txt');

    $html = Blade::render('<x-admin.file-select directory="assets" extensions="svg,webp" />');

    expect($html)->toContain('logo.svg')
        ->and($html)->not->toContain('readme.txt');
});

it('can include nested files', function (): void {
    Storage::disk('public')->put('assets/logo.svg', 'svg');
    Storage::disk('public')->put('assets/nested/photo.webp', 'webp');

    $flatHtml = Blade::render('<x-admin.file-select directory="assets" />');
    $recursiveHtml = Blade::render('<x-admin.file-select directory="assets" recursive />');

    expect($flatHtml)->not->toContain('nested/photo.webp')
        ->and($recursiveHtml)->toContain('nested/photo.webp');
});

it('renders a preview link for selected files', function (): void {
    Storage::disk('public')->put('assets/logo.svg', 'svg');

    $html = Blade::render('<x-admin.file-select directory="assets" selected="logo.svg" />');

    expect($html)->toContain('Ausgewählte Datei öffnen')
        ->and($html)->toContain(Storage::disk('public')->url('assets/logo.svg'));
});
