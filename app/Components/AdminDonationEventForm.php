<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\SaveDonationEventAction;
use App\Models\DonationEvent;
use Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AdminDonationEventForm extends Component
{
    public ?DonationEvent $donationEvent = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(?DonationEvent $donationEvent = null): void
    {
        $this->donationEvent = $donationEvent;
        $this->form = $this->formValues($donationEvent);
    }

    public function render(): Factory|View
    {
        return view('components.admin-donation-event-form');
    }

    public function save(SaveDonationEventAction $saveDonationEvent): void
    {
        abort_unless(Auth::check(), 403);

        $isCreating = ! $this->donationEvent instanceof DonationEvent;
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());

        $this->donationEvent = $saveDonationEvent($this->donationEvent, $validated['form']);
        $this->form = $this->formValues($this->donationEvent);

        Flux::toast(
            heading: 'Gespeichert',
            text: $isCreating ? 'Anlass wurde erstellt.' : 'Anlass wurde aktualisiert.',
            variant: 'success',
        );

        if ($isCreating) {
            $this->redirect(route('admin.donation-events.edit', $this->donationEvent), navigate: true);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['required', 'string', 'max:255', Rule::unique(DonationEvent::class, 'slug')->ignore($this->donationEvent)],
            'form.starts_at' => ['required', 'date'],
            'form.ends_at' => ['required', 'date', 'after:form.starts_at'],
            'form.registration_opens_at' => ['nullable', 'date'],
            'form.athlete_registration_closes_at' => ['nullable', 'date', 'after_or_equal:form.registration_opens_at'],
            'form.donor_registration_closes_at' => ['nullable', 'date', 'after_or_equal:form.registration_opens_at'],
            'form.location_name' => ['nullable', 'string', 'max:255'],
            'form.location_street' => ['nullable', 'string', 'max:255'],
            'form.location_postal_code' => ['nullable', 'string', 'max:255'],
            'form.location_city' => ['required', 'string', 'max:255'],
            'form.location_url' => ['nullable', 'url', 'max:255'],
            'form.is_published' => ['boolean'],
            'form.has_equal_split_option' => ['boolean'],
            'form.content.hero.copy_md' => ['nullable', 'string'],
            'form.content.home.about_heading' => ['nullable', 'string', 'max:255'],
            'form.content.home.about_intro_md' => ['nullable', 'string'],
            'form.content.home.about_body_md' => ['nullable', 'string'],
            'form.content.results.heading_md' => ['nullable', 'string', 'max:255'],
            'form.content.seo.meta_description_md' => ['nullable', 'string'],
            'form.content.seo.og_description_md' => ['nullable', 'string'],
            'form.content.invoice.additional_information' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'form.title' => 'Titel',
            'form.slug' => 'Kürzel',
            'form.starts_at' => 'Start',
            'form.ends_at' => 'Ende',
            'form.registration_opens_at' => 'Anmeldestart',
            'form.athlete_registration_closes_at' => 'Anmeldeschluss Sportler:innen',
            'form.donor_registration_closes_at' => 'Anmeldeschluss Spender:innen',
            'form.location_city' => 'Stadt',
            'form.location_url' => 'Kartenlink',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formValues(?DonationEvent $donationEvent): array
    {
        if (! $donationEvent instanceof DonationEvent) {
            return [
                'title' => '',
                'slug' => '',
                'starts_at' => '',
                'ends_at' => '',
                'registration_opens_at' => '',
                'athlete_registration_closes_at' => '',
                'donor_registration_closes_at' => '',
                'location_name' => '',
                'location_street' => '',
                'location_postal_code' => '',
                'location_city' => '',
                'location_url' => '',
                'is_published' => false,
                'has_equal_split_option' => true,
                'content' => $this->contentValues(null),
            ];
        }

        return [
            'title' => $donationEvent->title,
            'slug' => $donationEvent->slug,
            'starts_at' => $this->dateTimeValue($donationEvent->starts_at),
            'ends_at' => $this->dateTimeValue($donationEvent->ends_at),
            'registration_opens_at' => $this->dateTimeValue($donationEvent->registration_opens_at),
            'athlete_registration_closes_at' => $this->dateTimeValue($donationEvent->athlete_registration_closes_at),
            'donor_registration_closes_at' => $this->dateTimeValue($donationEvent->donor_registration_closes_at),
            'location_name' => $donationEvent->location_name ?? '',
            'location_street' => $donationEvent->location_street ?? '',
            'location_postal_code' => $donationEvent->location_postal_code ?? '',
            'location_city' => $donationEvent->location_city,
            'location_url' => $donationEvent->location_url ?? '',
            'is_published' => $donationEvent->is_published,
            'has_equal_split_option' => $donationEvent->has_equal_split_option,
            'content' => $this->contentValues($donationEvent),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function contentValues(?DonationEvent $donationEvent): array
    {
        return [
            'hero' => [
                'copy_md' => $donationEvent?->contentValue('hero.copy_md') ?? '',
            ],
            'home' => [
                'about_heading' => $donationEvent?->contentValue('home.about_heading') ?? '',
                'about_intro_md' => $donationEvent?->contentValue('home.about_intro_md') ?? '',
                'about_body_md' => $donationEvent?->contentValue('home.about_body_md') ?? '',
            ],
            'results' => [
                'heading_md' => $donationEvent?->contentValue('results.heading_md') ?? '',
            ],
            'seo' => [
                'meta_description_md' => $donationEvent?->contentValue('seo.meta_description_md') ?? '',
                'og_description_md' => $donationEvent?->contentValue('seo.og_description_md') ?? '',
            ],
            'invoice' => [
                'additional_information' => $donationEvent?->contentValue('invoice.additional_information') ?? '',
            ],
        ];
    }

    protected function dateTimeValue(mixed $value): string
    {
        return is_object($value) && method_exists($value, 'format')
            ? $value->format('Y-m-d\\TH:i:s')
            : '';
    }
}
