<?php

use App\Support\AdminFiles\SvgNormalizer;

it('adds an inherited stroke none to SVGs without a stroke', function (): void {
    $normalized = app(SvgNormalizer::class)->normalize('<svg xmlns="http://www.w3.org/2000/svg"><path fill="#fff" d="M0 0h1v1H0z"/></svg>');

    expect($normalized)->toContain('<svg xmlns="http://www.w3.org/2000/svg" stroke="none">');
});

it('preserves explicit root and descendant strokes', function (): void {
    $normalized = app(SvgNormalizer::class)->normalize('<svg xmlns="http://www.w3.org/2000/svg" stroke="#f00"><path stroke="#0f0" d="M0 0h1v1H0z"/></svg>');

    expect($normalized)->toContain('stroke="#f00"')
        ->and($normalized)->toContain('stroke="#0f0"');
});

it('does not change an already normalized SVG', function (): void {
    $normalizer = app(SvgNormalizer::class);
    $normalized = $normalizer->normalize('<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h1v1H0z"/></svg>');

    expect($normalizer->normalize($normalized))->toBe($normalized);
});

it('rejects malformed SVGs', function (): void {
    expect(fn (): string => app(SvgNormalizer::class)->normalize('<svg>'))
        ->toThrow(InvalidArgumentException::class, 'Datei enthält kein gültiges SVG.');
});
