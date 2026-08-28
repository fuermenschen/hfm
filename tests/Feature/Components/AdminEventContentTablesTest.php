<?php

use App\Components\AdminFaqEditor;
use App\Components\AdminFaqTable;
use App\Components\AdminPartnerEditor;
use App\Components\AdminPartnerTable;
use App\Components\AdminPersonTable;
use App\Components\AdminSponsorEditor;
use App\Components\AdminSponsorTable;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Models\User;
use App\Support\Datatable\DatatableValueFormatter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('renders partners in admin table and includes donation event assignment count', function (): void {
    $events = DonationEvent::factory()->count(2)->create();

    $assignedPartner = Partner::factory()->create([
        'name' => 'Bruehlgut Stiftung',
        'logo_light_filename' => 'bruehlgut_light.svg',
        'logo_dark_filename' => 'bruehlgut_dark.svg',
    ]);

    $unassignedPartner = Partner::factory()->create([
        'name' => 'Unassigned Partner',
    ]);

    $assignedPartner->donationEvents()->attach($events[0]->id, ['sort_order' => 1, 'is_published' => true]);
    $assignedPartner->donationEvents()->attach($events[1]->id, ['sort_order' => 1, 'is_published' => true]);

    Livewire::test(AdminPartnerTable::class)
        ->assertSee('Bruehlgut Stiftung')
        ->tap(function ($component) use ($assignedPartner, $unassignedPartner): void {
            $rows = $component->viewData('partners')->getCollection();

            expect($rows->firstWhere('id', $assignedPartner->id)?->donation_events_count)->toBe(2);
            expect($rows->firstWhere('id', $unassignedPartner->id)?->donation_events_count)->toBe(0);
        });
});

it('explains partner public content fields in German', function (): void {
    Livewire::test(AdminPartnerTable::class)
        ->assertSee('Dieses Logo wird auf der öffentlichen Startseite in der hellen Darstellung verwendet.')
        ->assertSee('Dieses Logo wird auf der öffentlichen Startseite in der dunklen Darstellung verwendet.')
        ->assertSee('Dieser allgemeine Kurztext beschreibt die begünstigte Organisation.')
        ->assertSee('Diese Adresse wird auf der öffentlichen Startseite mit der Partnerorganisation und ihrem Logo verlinkt.')
        ->assertSeeHtml('aria-label="Hinweis zu Kurztext"');
});

it('edits partners from admin table modal state', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/old-light.svg', 'svg');
    Storage::disk('public')->put('partners/old-dark.svg', 'svg');
    Storage::disk('public')->put('partners/nested/old-dark.svg', 'svg');
    Storage::disk('public')->put('partners/new-light.svg', 'svg');
    Storage::disk('public')->put('partners/new-dark.svg', 'svg');

    $partner = Partner::factory()->create([
        'name' => 'Old Partner',
        'logo_light_filename' => 'old-light.svg',
        'logo_dark_filename' => 'nested/old-dark.svg',
        'beneficiary_blurb' => 'Old text',
        'url' => 'https://old.example.test',
    ]);

    Livewire::test(AdminPartnerEditor::class)
        ->call('open', $partner->id)
        ->assertSet('partnerId', $partner->id)
        ->assertSet('modalOpen', true)
        ->assertSet('name', 'Old Partner')
        ->assertSet('logoLightFilename', 'old-light.svg')
        ->assertSet('logoDarkFilename', 'nested/old-dark.svg')
        ->set('name', 'New Partner')
        ->set('logoLightFilename', 'new-light.svg')
        ->set('logoDarkFilename', 'new-dark.svg')
        ->set('beneficiaryBlurb', 'New text')
        ->set('url', 'https://new.example.test')
        ->call('save')
        ->assertSet('partnerId', null)
        ->assertSet('modalOpen', false);

    $partner->refresh();

    expect($partner->name)->toBe('New Partner')
        ->and($partner->logo_light_filename)->toBe('new-light.svg')
        ->and($partner->logo_dark_filename)->toBe('new-dark.svg')
        ->and($partner->beneficiary_blurb)->toBe('New text')
        ->and($partner->url)->toBe('https://new.example.test');
});

it('creates and deletes partners from admin table modal state', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    Livewire::test(AdminPartnerEditor::class)
        ->call('open')
        ->assertSet('modalOpen', true)
        ->set('name', 'Created Partner')
        ->set('logoLightFilename', 'light.svg')
        ->set('logoDarkFilename', 'dark.svg')
        ->set('beneficiaryBlurb', 'Created text')
        ->set('url', 'https://created.example.test')
        ->call('save')
        ->assertSet('modalOpen', false)
        ->assertHasNoErrors();

    $partner = Partner::query()->where('name', 'Created Partner')->firstOrFail();

    expect($partner->logo_light_filename)->toBe('light.svg')
        ->and($partner->logo_dark_filename)->toBe('dark.svg')
        ->and($partner->beneficiary_blurb)->toBe('Created text')
        ->and($partner->url)->toBe('https://created.example.test');

    Livewire::test(AdminPartnerTable::class)
        ->call('confirmDeleteRow', $partner->id)
        ->assertSet('deletingId', $partner->id)
        ->assertSet('deletingLabel', 'Created Partner')
        ->call('deleteRow')
        ->assertSet('deletingId', null);

    expect(Partner::query()->whereKey($partner->id)->exists())->toBeFalse();
});

it('validates trimmed partner names for uniqueness', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');
    Partner::factory()->create(['name' => 'Existing Partner']);

    Livewire::test(AdminPartnerEditor::class)
        ->call('open')
        ->set('name', ' Existing Partner ')
        ->set('logoLightFilename', 'light.svg')
        ->set('logoDarkFilename', 'dark.svg')
        ->set('beneficiaryBlurb', 'Text')
        ->set('url', 'https://partner.example.test')
        ->call('save')
        ->assertHasErrors(['name' => 'unique'])
        ->assertSee('Dieser Name wird bereits verwendet.');
});

it('validates partner fields in German on update', function (): void {
    Livewire::test(AdminPartnerEditor::class)
        ->set('name', 'Partner')
        ->set('name', '')
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('Bitte gib einen Namen ein.');
});

it('requires partner public content when creating', function (string $field): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    Livewire::test(AdminPartnerEditor::class)
        ->call('open')
        ->set('name', 'Required Partner')
        ->set('logoLightFilename', 'light.svg')
        ->set('logoDarkFilename', 'dark.svg')
        ->set('beneficiaryBlurb', 'Required text')
        ->set('url', 'https://required.example.test')
        ->set($field, '')
        ->call('save')
        ->assertHasErrors([$field => 'required']);
})->with([
    'light logo' => 'logoLightFilename',
    'dark logo' => 'logoDarkFilename',
    'short text' => 'beneficiaryBlurb',
    'URL' => 'url',
]);

it('requires partner public content when editing', function (string $field): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    $partner = Partner::factory()->create([
        'logo_light_filename' => 'light.svg',
        'logo_dark_filename' => 'dark.svg',
    ]);

    Livewire::test(AdminPartnerEditor::class)
        ->call('open', $partner->id)
        ->set($field, '')
        ->call('save')
        ->assertHasErrors([$field => 'required']);
})->with([
    'light logo' => 'logoLightFilename',
    'dark logo' => 'logoDarkFilename',
    'short text' => 'beneficiaryBlurb',
    'URL' => 'url',
]);

it('rejects non-image partner logos', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/document.pdf', 'pdf');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    Livewire::test(AdminPartnerEditor::class)
        ->call('open')
        ->set('name', 'Invalid Logo Partner')
        ->set('logoLightFilename', 'document.pdf')
        ->set('logoDarkFilename', 'dark.svg')
        ->set('beneficiaryBlurb', 'Text')
        ->set('url', 'https://partner.example.test')
        ->call('save')
        ->assertHasErrors('logoLightFilename')
        ->assertSee('Bitte wähle ein verfügbares helles Logo aus.');

    expect(Partner::query()->where('name', 'Invalid Logo Partner')->exists())->toBeFalse();
});

it('does not delete partners assigned to events', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    $partner = Partner::factory()->create([
        'name' => 'Event Partner',
        'logo_light_filename' => 'light.svg',
        'logo_dark_filename' => 'dark.svg',
    ]);
    $event = DonationEvent::factory()->create();
    $partner->donationEvents()->attach($event->id, ['sort_order' => 1, 'is_published' => true]);

    Livewire::test(AdminPartnerTable::class)
        ->call('confirmDeleteRow', $partner->id)
        ->call('deleteRow')
        ->assertSet('deletingId', $partner->id)
        ->assertHasErrors('deletingId');

    expect(Partner::query()->whereKey($partner->id)->exists())->toBeTrue()
        ->and($event->partners()->whereKey($partner->id)->exists())->toBeTrue();
});

it('does not delete partners selected by athlete registrations', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    $partner = Partner::factory()->create([
        'name' => 'Registration Partner',
        'logo_light_filename' => 'light.svg',
        'logo_dark_filename' => 'dark.svg',
    ]);

    AthleteRegistration::factory()->withPartner($partner)->create();

    Livewire::test(AdminPartnerTable::class)
        ->call('confirmDeleteRow', $partner->id)
        ->call('deleteRow')
        ->assertSet('deletingId', $partner->id)
        ->assertHasErrors('deletingId');

    expect(Partner::query()->whereKey($partner->id)->exists())->toBeTrue();
});

it('rejects unauthenticated table mutations', function (): void {
    auth()->logout();

    Livewire::test(AdminPartnerEditor::class)
        ->call('open')
        ->assertForbidden();
});

it('renders sponsors in admin table and includes donation event assignment count', function (): void {
    $event = DonationEvent::factory()->create();

    $assignedSponsor = Sponsor::factory()->create([
        'name' => 'Rohner Spiller',
        'description' => 'Druckpartner',
        'logo_filename' => 'rohner_spiller.svg',
        'url' => 'https://example.test/rohner',
    ]);

    $unassignedSponsor = Sponsor::factory()->create([
        'name' => 'Unassigned Sponsor',
        'description' => 'Noch ohne Anlass',
        'logo_filename' => 'unassigned.svg',
    ]);

    $assignedSponsor->donationEvents()->attach($event->id, [
        'contribution_text' => 'Event contribution',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    Livewire::test(AdminSponsorTable::class)
        ->assertSee('Rohner Spiller')
        ->tap(function ($component) use ($assignedSponsor, $unassignedSponsor): void {
            $rows = $component->viewData('sponsors')->getCollection();

            expect($rows->firstWhere('id', $assignedSponsor->id)?->donation_events_count)->toBe(1);
            expect($rows->firstWhere('id', $unassignedSponsor->id)?->donation_events_count)->toBe(0);
        });
});

it('explains sponsor public content fields in German', function (): void {
    Livewire::test(AdminSponsorTable::class)
        ->assertSee('Diese allgemeine Beschreibung stellt die Sponsororganisation vor und erscheint im Detailfenster der Sponsorenkarte.')
        ->assertSee('Dieses Logo wird auf der öffentlichen Startseite als Sponsorenkarte angezeigt.')
        ->assertSee('Diese Adresse wird über die Schaltfläche «Zur Website» im Detailfenster der Sponsorenkarte geöffnet.')
        ->assertSeeHtml('aria-label="Hinweis zu Beschreibung"');
});

it('edits sponsors from admin table modal state', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/old-logo.svg', 'svg');
    Storage::disk('public')->put('sponsors/nested/new-logo.svg', 'svg');

    $sponsor = Sponsor::factory()->create([
        'name' => 'Old Sponsor',
        'description' => 'Old description',
        'logo_filename' => 'old-logo.svg',
        'url' => 'https://old.example.test',
    ]);

    Livewire::test(AdminSponsorEditor::class)
        ->call('open', $sponsor->id)
        ->assertSet('sponsorId', $sponsor->id)
        ->assertSet('modalOpen', true)
        ->assertSet('name', 'Old Sponsor')
        ->assertSet('logoFilename', 'old-logo.svg')
        ->set('name', 'New Sponsor')
        ->set('description', 'New description')
        ->set('logoFilename', 'nested/new-logo.svg')
        ->set('url', 'https://new.example.test')
        ->call('save')
        ->assertSet('sponsorId', null)
        ->assertSet('modalOpen', false);

    $sponsor->refresh();

    expect($sponsor->name)->toBe('New Sponsor')
        ->and($sponsor->description)->toBe('New description')
        ->and($sponsor->logo_filename)->toBe('nested/new-logo.svg')
        ->and($sponsor->url)->toBe('https://new.example.test');
});

it('creates and deletes sponsors from admin table modal state', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    Livewire::test(AdminSponsorEditor::class)
        ->call('open')
        ->assertSet('modalOpen', true)
        ->set('name', 'Created Sponsor')
        ->set('description', 'Created description')
        ->set('logoFilename', 'logo.svg')
        ->set('url', 'https://created-sponsor.example.test')
        ->call('save')
        ->assertSet('modalOpen', false)
        ->assertHasNoErrors();

    $sponsor = Sponsor::query()->where('name', 'Created Sponsor')->firstOrFail();

    expect($sponsor->description)->toBe('Created description')
        ->and($sponsor->logo_filename)->toBe('logo.svg')
        ->and($sponsor->url)->toBe('https://created-sponsor.example.test');

    Livewire::test(AdminSponsorTable::class)
        ->call('confirmDeleteRow', $sponsor->id)
        ->assertSet('deletingId', $sponsor->id)
        ->assertSet('deletingLabel', 'Created Sponsor')
        ->call('deleteRow')
        ->assertSet('deletingId', null);

    expect(Sponsor::query()->whereKey($sponsor->id)->exists())->toBeFalse();
});

it('validates trimmed sponsor names for uniqueness', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');
    Sponsor::factory()->create(['name' => 'Existing Sponsor']);

    Livewire::test(AdminSponsorEditor::class)
        ->call('open')
        ->set('name', ' Existing Sponsor ')
        ->set('description', 'Description')
        ->set('logoFilename', 'logo.svg')
        ->set('url', 'https://sponsor.example.test')
        ->call('save')
        ->assertHasErrors(['name' => 'unique'])
        ->assertSee('Dieser Name wird bereits verwendet.');
});

it('validates sponsor fields in German on update', function (): void {
    Livewire::test(AdminSponsorEditor::class)
        ->set('name', 'Sponsor')
        ->set('name', '')
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('Bitte gib einen Namen ein.');
});

it('requires sponsor public content when creating', function (string $field): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    Livewire::test(AdminSponsorEditor::class)
        ->call('open')
        ->set('name', 'Required Sponsor')
        ->set('description', 'Required description')
        ->set('logoFilename', 'logo.svg')
        ->set('url', 'https://required.example.test')
        ->set($field, '')
        ->call('save')
        ->assertHasErrors([$field => 'required']);
})->with([
    'description' => 'description',
    'logo' => 'logoFilename',
    'URL' => 'url',
]);

it('requires sponsor public content when editing', function (string $field): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    $sponsor = Sponsor::factory()->create(['logo_filename' => 'logo.svg']);

    Livewire::test(AdminSponsorEditor::class)
        ->call('open', $sponsor->id)
        ->set($field, '')
        ->call('save')
        ->assertHasErrors([$field => 'required']);
})->with([
    'description' => 'description',
    'logo' => 'logoFilename',
    'URL' => 'url',
]);

it('rejects non-image sponsor logos', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/document.pdf', 'pdf');

    Livewire::test(AdminSponsorEditor::class)
        ->call('open')
        ->set('name', 'Invalid Logo Sponsor')
        ->set('description', 'Description')
        ->set('logoFilename', 'document.pdf')
        ->set('url', 'https://sponsor.example.test')
        ->call('save')
        ->assertHasErrors('logoFilename')
        ->assertSee('Bitte wähle ein verfügbares Logo aus.');

    expect(Sponsor::query()->where('name', 'Invalid Logo Sponsor')->exists())->toBeFalse();
});

it('does not delete sponsors assigned to events', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    $sponsor = Sponsor::factory()->create([
        'name' => 'Event Sponsor',
        'description' => 'Assigned sponsor',
        'logo_filename' => 'logo.svg',
    ]);
    $event = DonationEvent::factory()->create();
    $sponsor->donationEvents()->attach($event->id, [
        'contribution_text' => 'Event contribution',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    Livewire::test(AdminSponsorTable::class)
        ->call('confirmDeleteRow', $sponsor->id)
        ->call('deleteRow')
        ->assertSet('deletingId', $sponsor->id)
        ->assertHasErrors('deletingId');

    expect(Sponsor::query()->whereKey($sponsor->id)->exists())->toBeTrue()
        ->and($event->sponsors()->whereKey($sponsor->id)->exists())->toBeTrue();
});

it('rejects unauthenticated exports', function (): void {
    auth()->logout();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])
        ->call('exportAll', 'csv')
        ->assertForbidden();
});

it('renders faqs in admin table with assigned event pills', function (): void {
    $events = DonationEvent::factory()->count(2)->create();

    $assignedFaq = Faq::query()->create([
        'title' => 'Wann und wo findet der Anlass statt?',
        'content_md' => 'Der Anlass findet in Winterthur statt.',
    ]);

    $unassignedFaq = Faq::query()->create([
        'title' => 'Unassigned FAQ',
        'content_md' => 'Noch keinem Anlass zugeordnet.',
    ]);

    $assignedFaq->donationEvents()->attach($events[0]->id, ['group' => 'general', 'sort_order' => 10, 'is_published' => true]);
    $assignedFaq->donationEvents()->attach($events[1]->id, ['group' => 'general', 'sort_order' => 10, 'is_published' => true]);

    Livewire::test(AdminFaqTable::class)
        ->assertSee('Wann und wo findet der Anlass statt?')
        ->assertSee($events[0]->slug)
        ->assertSee($events[1]->slug)
        ->tap(function ($component) use ($assignedFaq, $unassignedFaq): void {
            $table = $component->instance();

            expect($table->linkedEvents($assignedFaq)->count())->toBe(2)
                ->and($table->linkedEvents($unassignedFaq)->count())->toBe(0);
        });
});

it('applies configured truncation for tooltip columns in event content tables', function (): void {
    $longPartnerUrl = 'https://example.test/'.str_repeat('partner-segment/', 8);
    $longSponsorUrl = 'https://example.test/'.str_repeat('sponsor-segment/', 8);
    $longFaqContent = str_repeat('Ausfuehrlicher FAQ Inhalt ', 8);

    Partner::factory()->create([
        'name' => 'Long Partner',
        'url' => $longPartnerUrl,
    ]);

    Sponsor::factory()->create([
        'name' => 'Long Sponsor',
        'description' => 'Beschreibung',
        'logo_filename' => 'long.svg',
        'url' => $longSponsorUrl,
    ]);

    Faq::query()->create([
        'title' => 'Long FAQ',
        'content_md' => $longFaqContent,
    ]);

    $formatter = app(DatatableValueFormatter::class);

    Livewire::test(AdminPartnerTable::class)
        ->assertSee($formatter->truncate($longPartnerUrl, 48));

    Livewire::test(AdminSponsorTable::class)
        ->assertSee($formatter->truncate($longSponsorUrl, 48));

    Livewire::test(AdminFaqTable::class)
        ->assertSee($formatter->truncate($longFaqContent, 60));
});

it('edits faqs from admin table modal state after confirming changes', function (): void {
    $logSpy = Log::spy();

    $faq = Faq::query()->create([
        'title' => 'Alte Frage',
        'content_md' => 'Alte Antwort',
    ]);

    Livewire::test(AdminFaqEditor::class)
        ->call('open', $faq->id)
        ->assertSet('faqId', $faq->id)
        ->assertSet('modalOpen', true)
        ->assertSet('title', 'Alte Frage')
        ->assertSet('contentMd', 'Alte Antwort')
        ->set('title', 'Neue Frage')
        ->set('contentMd', 'Neue Antwort')
        ->call('save')
        ->assertSet('confirmingSave', true)
        ->assertHasNoErrors()
        ->call('confirmSave')
        ->assertSet('modalOpen', false)
        ->assertHasNoErrors();

    $faq->refresh();

    expect($faq->title)->toBe('Neue Frage')
        ->and($faq->content_md)->toBe('Neue Antwort');

    $logSpy->shouldHaveReceived('info')
        ->with('Admin editor save confirmed.', [
            'editor' => 'AdminFaqEditor',
            'fields' => ['title', 'contentMd'],
            'admin' => auth()->user()->name,
            'faq_id' => $faq->id,
        ])
        ->once();
});

it('creates and deletes faqs from admin table modal state', function (): void {
    Livewire::test(AdminFaqEditor::class)
        ->call('open')
        ->assertSet('modalOpen', true)
        ->set('title', 'Neue FAQ')
        ->set('contentMd', 'Neue Antwort')
        ->call('save')
        ->assertSet('confirmingSave', true)
        ->assertHasNoErrors()
        ->call('confirmSave')
        ->assertSet('modalOpen', false)
        ->assertHasNoErrors();

    $faq = Faq::query()->where('title', 'Neue FAQ')->firstOrFail();

    expect($faq->content_md)->toBe('Neue Antwort');

    Livewire::test(AdminFaqTable::class)
        ->call('confirmDeleteRow', $faq->id)
        ->assertSet('deletingId', $faq->id)
        ->assertSet('deletingLabel', 'Neue FAQ')
        ->call('deleteRow')
        ->assertSet('deletingId', null);

    expect(Faq::query()->whereKey($faq->id)->exists())->toBeFalse();
});

it('renders a markdown preview like the public page', function (): void {
    Livewire::test(AdminFaqEditor::class)
        ->set('title', 'Was ist Markdown?')
        ->set('contentMd', "**Fett** und *kursiv*\n\n[Link](https://example.org)")
        ->assertSeeHtml('<strong>Fett</strong>')
        ->assertSeeHtml('<em>kursiv</em>')
        ->assertSeeHtml('<a href="https://example.org">Link</a>');
});

it('validates faq fields in German', function (): void {
    Livewire::test(AdminFaqEditor::class)
        ->set('title', 'Frage')
        ->set('title', '')
        ->assertHasErrors(['title' => 'required'])
        ->assertSee('Bitte gib eine Frage ein.');
});

it('rejects whitespace-only faq answers', function (): void {
    Livewire::test(AdminFaqEditor::class)
        ->call('open')
        ->set('title', '  Nur Leerzeichen  ')
        ->set('contentMd', '   ')
        ->call('save')
        ->assertHasErrors(['contentMd' => 'required']);
});

it('does not delete faqs assigned to events', function (): void {
    $faq = Faq::query()->create([
        'title' => 'Zugeordnete FAQ',
        'content_md' => 'Antwort',
    ]);
    $event = DonationEvent::factory()->create();
    $faq->donationEvents()->attach($event->id, ['group' => 'general', 'sort_order' => 10, 'is_published' => true]);

    Livewire::test(AdminFaqTable::class)
        ->call('confirmDeleteRow', $faq->id)
        ->call('deleteRow')
        ->assertSet('deletingId', $faq->id)
        ->assertHasErrors('deletingId');

    expect(Faq::query()->whereKey($faq->id)->exists())->toBeTrue()
        ->and($event->faqs()->whereKey($faq->id)->exists())->toBeTrue();
});

it('rejects unauthenticated faq mutations', function (): void {
    auth()->logout();

    Livewire::test(AdminFaqEditor::class)
        ->call('open')
        ->assertForbidden();
});
