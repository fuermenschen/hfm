<?php

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use function Pest\Laravel\get;

test('keeps public pages indexable', function () {
    get('/')
        ->assertSee('<meta name="robots" content="index, follow" />', escape: false)
        ->assertSee('<meta name="yandex" content="noindex, nofollow" />', escape: false)
        ->assertSee('<meta name="baiduspider" content="noindex, nofollow" />', escape: false)
        ->assertHeaderMissing('X-Robots-Tag');
});

test('renders page-specific canonical and Open Graph metadata', function () {
    $response = get('/fragen-und-antworten?utm_source=campaign');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());

    expect(preg_replace('/\s+/', ' ', trim($document->getElementsByTagName('title')->item(0)->textContent)))
        ->toBe('Fragen und Antworten - '.config('app.name'));
    expect((new DOMXPath($document))->evaluate('string(//meta[@property="og:description"]/@content)'))
        ->toBe('Informationen zu Ablauf, Teilnahme und Spenden beim Spendenlauf Höhenmeter für Menschen in Winterthur.');

    $response
        ->assertSee('name="description"', escape: false)
        ->assertSee('content="Informationen zu Ablauf, Teilnahme und Spenden beim Spendenlauf Höhenmeter für Menschen in Winterthur."', escape: false)
        ->assertSee('<link rel="canonical" href="'.url('/fragen-und-antworten').'" />', escape: false)
        ->assertSee('<meta property="og:title" content="Fragen und Antworten" />', escape: false)
        ->assertSee('<meta property="og:url" content="'.url('/fragen-und-antworten').'" />', escape: false)
        ->assertSee('<meta property="og:locale" content="de_CH" />', escape: false)
        ->assertSee('<meta property="og:image" content="'.asset('favicons/social_media_preview.png').'" />', escape: false)
        ->assertSee('<meta property="og:image:type" content="image/png" />', escape: false)
        ->assertSee('<meta property="og:image:width" content="1201" />', escape: false)
        ->assertSee('<meta property="og:image:height" content="631" />', escape: false)
        ->assertDontSee('name="keywords"', escape: false)
        ->assertDontSee('name="language"', escape: false)
        ->assertDontSee('name="rating"', escape: false)
        ->assertDontSee('name="og:title"', escape: false);
});

test('marks login and signed unsubscribe pages as noindex', function () {
    get(route('login'))
        ->assertSee('<meta name="robots" content="noindex, nofollow" />', escape: false)
        ->assertDontSee('rel="canonical"', escape: false)
        ->assertDontSee('property="og:', escape: false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    get(route('login-uuid', ['uuid' => Str::uuid()]))
        ->assertSee('<meta name="robots" content="noindex, nofollow" />', escape: false);

    $unsubscribeUrl = URL::temporarySignedRoute(
        'newsletter.unsubscribe',
        now()->addDay(),
        ['email' => 'anna@example.com'],
    );

    get($unsubscribeUrl)
        ->assertSee('<meta name="robots" content="noindex, nofollow" />', escape: false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('marks admin and portal routes noindex before authentication redirects', function () {
    foreach (['/admin', '/admin/partner', '/portal', '/portal/teilnahmen'] as $path) {
        get($path)
            ->assertRedirect(route('login'))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
});

test('omits the login page from the sitemap', function () {
    expect(file_get_contents(public_path('sitemap.xml')))
        ->not->toContain('<loc>https://hfm-winti.ch/login</loc>');
});
