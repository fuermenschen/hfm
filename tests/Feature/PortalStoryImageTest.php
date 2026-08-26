<?php

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\AthleteStoryImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('generates event and athlete specific story images', function (): void {
    Storage::fake('public');

    $event = DonationEvent::factory()->year(2036)->create(['title' => 'Winterlauf für alle']);
    foreach (['bruehlgut', 'iks', '143'] as $index => $logo) {
        Storage::disk('public')->put('partners/'.$logo.'_light.svg', File::get(resource_path('images/'.$logo.'_light.svg')));
        Storage::disk('public')->put('partners/'.$logo.'_dark.svg', File::get(resource_path('images/'.$logo.'_dark.svg')));

        $event->partners()->attach(Partner::factory()->create([
            'logo_light_filename' => $logo.'_light.svg',
            'logo_dark_filename' => $logo.'_dark.svg',
        ]), ['is_published' => true, 'sort_order' => ($index + 1) * 10]);
    }
    $athlete = ExternalUser::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
    ]);
    $registration = AthleteRegistration::factory()
        ->forVerifiedEventUser($event, $athlete)
        ->create();

    $service = app(AthleteStoryImageService::class);
    $image = $service->build($registration, StoryImageVariant::Light);
    $logosMethod = new ReflectionMethod(AthleteStoryImageService::class, 'partnerLogos');
    $baseCacheKeyMethod = new ReflectionMethod(AthleteStoryImageService::class, 'baseCacheKey');
    $cacheKeyMethod = new ReflectionMethod(AthleteStoryImageService::class, 'cacheKey');
    $partnerLogos = $logosMethod->invoke($service, $event, StoryImageVariant::Light);
    $baseCacheKey = $baseCacheKeyMethod->invoke($service, $event, StoryImageVariant::Light, $partnerLogos);
    $cacheKey = $cacheKeyMethod->invoke($service, $registration, StoryImageVariant::Light, $baseCacheKey);

    expect($image['filename'])->toBe('story_single_light_'.$athlete->public_id_string.'.jpg')
        ->and(getimagesizefromstring($image['contents']))->toMatchArray(['0' => 1080, '1' => 1920])
        ->and(Cache::has($baseCacheKey))->toBeTrue()
        ->and(Cache::has($cacheKey))->toBeTrue();

    Cache::forever($cacheKey, ['contents' => 'cached-image', 'filename' => 'cached.jpg']);

    expect($service->build($registration, StoryImageVariant::Light))->toBe([
        'contents' => 'cached-image',
        'filename' => 'cached.jpg',
    ]);

    Storage::disk('public')->put('partners/bruehlgut_light.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
    $changedPartnerLogos = $logosMethod->invoke($service, $event, StoryImageVariant::Light);
    $changedBaseCacheKey = $baseCacheKeyMethod->invoke($service, $event, StoryImageVariant::Light, $changedPartnerLogos);
    $changedCacheKey = $cacheKeyMethod->invoke($service, $registration, StoryImageVariant::Light, $changedBaseCacheKey);

    expect($changedBaseCacheKey)->not->toBe($baseCacheKey)
        ->and($changedCacheKey)->not->toBe($cacheKey);
});

it('shares an event base image between athlete caches', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $firstRegistration = AthleteRegistration::factory()->forVerifiedEventUser($event, ExternalUser::factory()->create())->create();
    $secondRegistration = AthleteRegistration::factory()->forVerifiedEventUser($event, ExternalUser::factory()->create())->create();
    $service = app(AthleteStoryImageService::class);
    $logosMethod = new ReflectionMethod(AthleteStoryImageService::class, 'partnerLogos');
    $baseCacheKeyMethod = new ReflectionMethod(AthleteStoryImageService::class, 'baseCacheKey');
    $cacheKeyMethod = new ReflectionMethod(AthleteStoryImageService::class, 'cacheKey');
    $partnerLogos = $logosMethod->invoke($service, $event, StoryImageVariant::Light);
    $baseCacheKey = $baseCacheKeyMethod->invoke($service, $event, StoryImageVariant::Light, $partnerLogos);

    $service->build($firstRegistration, StoryImageVariant::Light);
    $service->build($secondRegistration, StoryImageVariant::Light);

    expect(Cache::has($baseCacheKey))->toBeTrue()
        ->and($cacheKeyMethod->invoke($service, $firstRegistration, StoryImageVariant::Light, $baseCacheKey))
        ->not->toBe($cacheKeyMethod->invoke($service, $secondRegistration, StoryImageVariant::Light, $baseCacheKey));
});

it('renders SVG logos without black pixels before scaling', function (): void {
    $service = app(AthleteStoryImageService::class);
    $method = new ReflectionMethod(AthleteStoryImageService::class, 'decodeSvg');
    $method->setAccessible(true);
    $logo = $method->invoke(
        $service,
        new ImageManager(Driver::class),
        resource_path('images/vbk_dark.svg'),
        '#1b2e47',
    )->scale(width: 258, height: 160);

    $native = $logo->core()->native();
    $minimumRed = $native->getImageChannelStatistics()[Imagick::CHANNEL_RED]['minima'];

    expect($minimumRed)->toBeGreaterThanOrEqual(27 * 257);
});

it('wraps event titles into balanced lines', function (): void {
    $service = app(AthleteStoryImageService::class);
    $method = new ReflectionMethod(AthleteStoryImageService::class, 'layoutTitle');
    $method->setAccessible(true);
    $layout = fn (string $title): array => $method->invoke($service, $title, resource_path('fonts/darkmode_on_xbold.otf'));

    $long = $layout->call($service, 'Höhenmeter für Menschen Winterlauf');
    expect($long['lines'])->toBe(['Höhenmeter für', 'Menschen Winterlauf'])
        ->and($long['fontSize'])->toBeGreaterThan(60)
        ->and($long['fontSize'])->toBeLessThan(115);

    $short = $layout->call($service, 'Winterlauf');
    expect($short['lines'])->toBe(['Winterlauf'])
        ->and($short['fontSize'])->toBe(115);

    $huge = $layout->call($service, 'Ein extrem langer Anlasstitel der niemals aufhört zu wachsen und wachsen');
    expect($huge['lines'])->toHaveCount(2)
        ->and($huge['fontSize'])->toBe(60);
});

it('allows athletes to retrieve only their own published event images', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $unverifiedEvent = DonationEvent::factory()->year(2037)->create();
    $unpublishedEvent = DonationEvent::factory()->year(2035)->create(['is_published' => false]);
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();
    $unverifiedRegistration = AthleteRegistration::factory()->forEvent($unverifiedEvent)->forExternalUser($athlete)->create(['verified' => false]);
    $unpublishedRegistration = AthleteRegistration::factory()->forVerifiedEventUser($unpublishedEvent, $athlete)->create();
    $stranger = ExternalUser::factory()->create();
    $strangerRegistration = AthleteRegistration::factory()->forVerifiedEventUser($event, $stranger)->create();

    actingAs($athlete, 'external');

    get(route('portal.story-image.download', [$registration, StoryImageVariant::Dark->value]))
        ->assertDownload('story_single_dark_'.$athlete->public_id_string.'.jpg');

    get(route('portal.story-image.preview', [$registration, StoryImageVariant::Dark->value]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/jpeg');

    get(route('portal.story-image.download', [$unverifiedRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.preview', [$unverifiedRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.download', [$strangerRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.preview', [$strangerRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();

    get(route('portal.story-image.download', [$unpublishedRegistration, StoryImageVariant::Light->value]))
        ->assertNotFound();
});

it('allows donors with pending donations to retrieve supported athlete images', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $donor = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();
    Donation::factory()->forPair($donor, $registration)->create(['verified' => false]);

    actingAs($donor, 'external');

    get(route('portal.story-image.download', [$registration, StoryImageVariant::Light->value]))
        ->assertDownload('story_single_light_'.$athlete->public_id_string.'.jpg');
});

it('denies story images to external users without a donation or athlete registration', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $stranger = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    actingAs($stranger, 'external');

    get(route('portal.story-image.download', [$registration, StoryImageVariant::Light->value]))
        ->assertNotFound();
});

it('shows personalized story sharing on athlete participation pages', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Deine Runden können noch mehr bewegen')
        ->assertSeeText('Gewinne weitere Spender:innen für deine Spendenaktion: Teile personalisierte Bilder und Texte.')
        ->assertSeeText('Story teilen')
        ->assertSeeText('Bild wird vorbereitet')
        ->assertSee('transition-opacity')
        ->assertSeeText('Kurz & direkt')
        ->assertSeeText('Etwas ausführlicher')
        ->assertSeeText('Für eine gute Sache')
        ->assertSeeText('Text kopieren')
        ->assertSeeText('Story-Bild herunterladen')
        ->assertSee(route('become-donor', ['sportlerin' => $athlete->public_id_string]), false)
        ->assertSee(route('portal.story-image.download', [$registration, 'light']), false)
        ->assertSee(route('portal.story-image.download', [$registration, 'dark']), false)
        ->assertSee(route('portal.story-image.preview', [$registration, 'light']), false)
        ->assertSee(route('portal.story-image.preview', [$registration, 'dark']), false);
});

it('shows welcome letter downloads as secondary participation documents', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Dokumente')
        ->assertSeeText('Willkommensbrief herunterladen')
        ->assertSee(route('portal.welcome-letter.download', $registration), false);
});

it('shows story image downloads for donors', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    $donor = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();
    Donation::factory()->forPair($donor, $registration)->create(['verified' => true]);

    actingAs($donor, 'external');

    get(route('portal.donations'))
        ->assertSuccessful()
        ->assertSeeText('Weitere Spender:innen für '.$athlete->privacy_name.' ('.$athlete->public_id_string.') finden')
        ->assertSeeText('Diese personalisierte Story kannst du direkt weitergeben.')
        ->assertSeeText('Story teilen')
        ->assertDontSeeText('Text kopieren')
        ->assertDontSeeText('Story-Bild hell')
        ->assertSee(route('portal.story-image.download', [$registration, 'light']), false)
        ->assertSee(route('portal.story-image.download', [$registration, 'dark']), false);
});

it('renders share text for equal split participations', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create(['partner_id' => null]);

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('100 % deiner Spende geht an die Benefizpartner des Anlasses.');
});

it('hides story sharing for unconfirmed participations', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    $athlete = ExternalUser::factory()->create();
    AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create(['verified' => false]);

    actingAs($athlete, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertDontSeeText('Deine Spendenaktion teilen');
});
