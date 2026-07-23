<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;

it('renders hero with class hooks for responsive control', function () {
    $html = Blade::render('<x-home-hero img="2" />');

    expect($html)
        ->toContain('hfm-hero__title')
        ->toContain('text-balance')
        ->toContain('wrap-break-word')
        ->and($html)->toContain('hfm-hero__copy')
        ->and($html)->toContain('hfm-hero__kicker')
        ->and($html)->toContain('hfm-hero__ctas');
});

it('falls back to an available hero image', function () {
    Vite::shouldReceive('asset')
        ->with('resources/images/landing_page/2.png')
        ->times(3)
        ->andReturn('/build/2.png');
    Vite::shouldReceive('asset')
        ->with('resources/images/hero_badge.svg')
        ->once()
        ->andReturn('/build/hero_badge.svg');

    $html = Blade::render('<x-home-hero img="1" />');

    expect($html)
        ->toContain('data-src="/build/2.png"')
        ->and(file_exists(resource_path('images/landing_page/1.png')))->toBeFalse();
});
