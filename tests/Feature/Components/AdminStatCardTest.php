<?php

use Illuminate\Support\Facades\Blade;

it('keeps tooltip controls optional and preserves the card destination', function (): void {
    $html = Blade::render('<x-admin.stat-card title="Test metric" value="42" route="admin.donors.index" :route-parameters="[\'anlass\' => \'2026\']" />');

    expect($html)->toContain(route('admin.donors.index', ['anlass' => '2026']), 'wire:navigate.hover', 'Test metric', '42');
    expect(str_contains($html, 'data-flux-tooltip'))->toBeFalse();
});

it('renders a toggleable tooltip with its requested icon outside the navigation link', function (string $icon): void {
    $html = Blade::render('<x-admin.stat-card title="Test metric" value="42" route="admin.donors.index" tooltip="Metric explanation" :tooltip-icon="$icon" />', ['icon' => $icon]);
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $reference = new DOMDocument;
    $reference->loadHTML(Blade::render('<flux:icon.'.$icon.' />'), LIBXML_NOERROR | LIBXML_NOWARNING);
    $referenceXPath = new DOMXPath($reference);

    expect($html)->toContain('Metric explanation', 'Hinweis zu Test metric')
        ->and($xpath->evaluate('count(//a//button)'))->toBe(0.0)
        ->and($xpath->evaluate('count(//button)'))->toBe(1.0)
        ->and($xpath->evaluate('string(//button//svg/path/@d)'))->toBe($referenceXPath->evaluate('string(//svg/path/@d)'));
})->with(['information-circle', 'question-mark-circle']);

it('uses the info icon by default and supports rich tooltip slot content', function (): void {
    $html = Blade::render('<x-admin.stat-card title="Test metric" value="42" route="admin.donors.index"><x-slot:tooltip><p>First paragraph.</p><p>Second paragraph.</p></x-slot:tooltip></x-admin.stat-card>');
    $default = Blade::render('<x-admin.stat-card title="Test metric" value="42" route="admin.donors.index" tooltip="Explanation" />');

    expect($html)->toContain('<p>First paragraph.</p>', '<p>Second paragraph.</p>', 'Hinweis zu Test metric')
        ->and($default)->toContain('Explanation', 'Hinweis zu Test metric');
});
