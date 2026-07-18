<?php

use App\Components\AdminExternalUserTable;
use App\Components\AdminFaqTable;
use App\Components\AdminPartnerTable;
use App\Components\AdminSponsorTable;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Models\User;
use App\Support\Datatable\DatatableValueFormatter;
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

it('edits partners from admin table modal state', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/old-light.svg', 'svg');
    Storage::disk('public')->put('partners/old-dark.svg', 'svg');
    Storage::disk('public')->put('partners/nested/old-dark.svg', 'svg');
    Storage::disk('public')->put('partners/new-light.svg', 'svg');

    $partner = Partner::factory()->create([
        'name' => 'Old Partner',
        'logo_light_filename' => 'old-light.svg',
        'logo_dark_filename' => 'nested/old-dark.svg',
        'beneficiary_blurb' => 'Old text',
        'url' => 'https://old.example.test',
    ]);

    Livewire::test(AdminPartnerTable::class)
        ->call('openEdit', $partner->id)
        ->assertSet('editingId', $partner->id)
        ->assertSet('editModalOpen', true)
        ->assertSet('editForm.name', 'Old Partner')
        ->assertSet('editForm.logo_light_filename', 'old-light.svg')
        ->assertSet('editForm.logo_dark_filename', 'nested/old-dark.svg')
        ->set('editForm.name', 'New Partner')
        ->set('editForm.logo_light_filename', 'new-light.svg')
        ->set('editForm.url', 'https://new.example.test')
        ->call('saveEdit')
        ->assertSet('editingId', null)
        ->assertSet('editModalOpen', false);

    $partner->refresh();

    expect($partner->name)->toBe('New Partner')
        ->and($partner->logo_light_filename)->toBe('new-light.svg')
        ->and($partner->url)->toBe('https://new.example.test');
});

it('creates and deletes partners from admin table modal state', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    Livewire::test(AdminPartnerTable::class)
        ->call('openCreate')
        ->assertSet('createModalOpen', true)
        ->set('createForm.name', 'Created Partner')
        ->set('createForm.logo_light_filename', 'light.svg')
        ->set('createForm.logo_dark_filename', 'dark.svg')
        ->set('createForm.beneficiary_blurb', 'Created text')
        ->set('createForm.url', 'https://created.example.test')
        ->call('saveCreate')
        ->assertSet('createModalOpen', false)
        ->assertHasNoErrors();

    $partner = Partner::query()->where('name', 'Created Partner')->firstOrFail();

    Livewire::test(AdminPartnerTable::class)
        ->call('confirmDeleteRow', $partner->id)
        ->assertSet('deletingId', $partner->id)
        ->assertSet('deletingLabel', 'Created Partner')
        ->call('deleteRow')
        ->assertSet('deletingId', null);

    expect(Partner::query()->whereKey($partner->id)->exists())->toBeFalse();
});

it('requires partner public content when creating', function (string $field): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/light.svg', 'svg');
    Storage::disk('public')->put('partners/dark.svg', 'svg');

    Livewire::test(AdminPartnerTable::class)
        ->call('openCreate')
        ->set('createForm.name', 'Required Partner')
        ->set('createForm.logo_light_filename', 'light.svg')
        ->set('createForm.logo_dark_filename', 'dark.svg')
        ->set('createForm.beneficiary_blurb', 'Required text')
        ->set('createForm.url', 'https://required.example.test')
        ->set('createForm.'.$field, '')
        ->call('saveCreate')
        ->assertHasErrors(['createForm.'.$field => 'required']);
})->with([
    'light logo' => 'logo_light_filename',
    'dark logo' => 'logo_dark_filename',
    'short text' => 'beneficiary_blurb',
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

    Livewire::test(AdminPartnerTable::class)
        ->call('openEdit', $partner->id)
        ->set('editForm.'.$field, '')
        ->call('saveEdit')
        ->assertHasErrors(['editForm.'.$field => 'required']);
})->with([
    'light logo' => 'logo_light_filename',
    'dark logo' => 'logo_dark_filename',
    'short text' => 'beneficiary_blurb',
    'URL' => 'url',
]);

it('rejects non-image partner logos', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('partners/document.pdf', 'pdf');

    Livewire::test(AdminPartnerTable::class)
        ->call('openCreate')
        ->set('createForm.name', 'Invalid Logo Partner')
        ->set('createForm.logo_light_filename', 'document.pdf')
        ->call('saveCreate')
        ->assertHasErrors('createForm.logo_light_filename');

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

    Livewire::test(AdminPartnerTable::class)
        ->call('openCreate')
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

    Livewire::test(AdminSponsorTable::class)
        ->call('openEdit', $sponsor->id)
        ->assertSet('editingId', $sponsor->id)
        ->assertSet('editModalOpen', true)
        ->assertSet('editForm.name', 'Old Sponsor')
        ->assertSet('editForm.logo_filename', 'old-logo.svg')
        ->set('editForm.name', 'New Sponsor')
        ->set('editForm.description', 'New description')
        ->set('editForm.logo_filename', 'nested/new-logo.svg')
        ->set('editForm.url', 'https://new.example.test')
        ->call('saveEdit')
        ->assertSet('editingId', null)
        ->assertSet('editModalOpen', false);

    $sponsor->refresh();

    expect($sponsor->name)->toBe('New Sponsor')
        ->and($sponsor->description)->toBe('New description')
        ->and($sponsor->logo_filename)->toBe('nested/new-logo.svg')
        ->and($sponsor->url)->toBe('https://new.example.test');
});

it('creates and deletes sponsors from admin table modal state', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    Livewire::test(AdminSponsorTable::class)
        ->call('openCreate')
        ->assertSet('createModalOpen', true)
        ->set('createForm.name', 'Created Sponsor')
        ->set('createForm.description', 'Created description')
        ->set('createForm.logo_filename', 'logo.svg')
        ->set('createForm.url', 'https://created-sponsor.example.test')
        ->call('saveCreate')
        ->assertSet('createModalOpen', false)
        ->assertHasNoErrors();

    $sponsor = Sponsor::query()->where('name', 'Created Sponsor')->firstOrFail();

    Livewire::test(AdminSponsorTable::class)
        ->call('confirmDeleteRow', $sponsor->id)
        ->assertSet('deletingId', $sponsor->id)
        ->assertSet('deletingLabel', 'Created Sponsor')
        ->call('deleteRow')
        ->assertSet('deletingId', null);

    expect(Sponsor::query()->whereKey($sponsor->id)->exists())->toBeFalse();
});

it('requires sponsor public content when creating', function (string $field): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    Livewire::test(AdminSponsorTable::class)
        ->call('openCreate')
        ->set('createForm.name', 'Required Sponsor')
        ->set('createForm.description', 'Required description')
        ->set('createForm.logo_filename', 'logo.svg')
        ->set('createForm.url', 'https://required.example.test')
        ->set('createForm.'.$field, '')
        ->call('saveCreate')
        ->assertHasErrors(['createForm.'.$field => 'required']);
})->with([
    'description' => 'description',
    'logo' => 'logo_filename',
    'URL' => 'url',
]);

it('requires sponsor public content when editing', function (string $field): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/logo.svg', 'svg');

    $sponsor = Sponsor::factory()->create(['logo_filename' => 'logo.svg']);

    Livewire::test(AdminSponsorTable::class)
        ->call('openEdit', $sponsor->id)
        ->set('editForm.'.$field, '')
        ->call('saveEdit')
        ->assertHasErrors(['editForm.'.$field => 'required']);
})->with([
    'description' => 'description',
    'logo' => 'logo_filename',
    'URL' => 'url',
]);

it('rejects non-image sponsor logos', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('sponsors/document.pdf', 'pdf');

    Livewire::test(AdminSponsorTable::class)
        ->call('openCreate')
        ->set('createForm.name', 'Invalid Logo Sponsor')
        ->set('createForm.logo_filename', 'document.pdf')
        ->call('saveCreate')
        ->assertHasErrors('createForm.logo_filename');

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

    Livewire::test(AdminExternalUserTable::class)
        ->call('exportAll', 'csv')
        ->assertForbidden();
});

it('renders faqs in admin table and includes donation event assignment count', function (): void {
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
        ->tap(function ($component) use ($assignedFaq, $unassignedFaq): void {
            $rows = $component->viewData('faqs')->getCollection();

            expect($rows->firstWhere('id', $assignedFaq->id)?->donation_events_count)->toBe(2);
            expect($rows->firstWhere('id', $unassignedFaq->id)?->donation_events_count)->toBe(0);
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
