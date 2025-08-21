<?php

use Illuminate\Support\Facades\Blade;

it('renders hero with class hooks for responsive control', function () {
    $html = Blade::render('<x-home-hero />');

    expect($html)
        ->toContain('hfm-hero__title')
        ->and($html)->toContain('hfm-hero__copy')
        ->and($html)->toContain('hfm-hero__kicker')
        ->and($html)->toContain('hfm-hero__ctas');
});
