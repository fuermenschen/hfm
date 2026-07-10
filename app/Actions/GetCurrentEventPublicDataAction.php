<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GetCurrentEventPublicDataAction
{
    /**
     * @return array{
     *     partners: Collection<int, Partner>,
     *     sponsors: Collection<int, Sponsor>,
     *     faqs: Collection<int, Faq>,
     * }
     */
    public function __invoke(?DonationEvent $event): array
    {
        $cacheKey = 'event_public_data_'.($event->id ?? 'none');

        /** @var array{partners: array<int, array<string, mixed>>, sponsors: array<int, array<string, mixed>>, faqs: array<int, array<string, mixed>>} $cached */
        $cached = Cache::remember($cacheKey, now()->addMinute(), function () use ($event): array {
            return [
                'partners' => $this->partners($event)->map(fn (Partner $p) => $p->toArray())->values()->all(),
                'sponsors' => $this->sponsors($event)->map(fn (Sponsor $s) => $s->toArray())->values()->all(),
                'faqs' => $this->faqs($event)->map(fn (Faq $f) => $f->toArray())->values()->all(),
            ];
        });

        return [
            'partners' => $this->hydratePartners($cached['partners']),
            'sponsors' => $this->hydrateSponsors($cached['sponsors']),
            'faqs' => $this->hydrateFaqs($cached['faqs']),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Partner>
     */
    protected function hydratePartners(array $rows): Collection
    {
        return Partner::query()->hydrate($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Sponsor>
     */
    protected function hydrateSponsors(array $rows): Collection
    {
        $sponsors = Sponsor::query()->hydrate($rows);
        $indexed = $sponsors->values();

        foreach (array_values($rows) as $i => $row) {
            $sponsor = $indexed->get($i);
            if ($sponsor === null) {
                continue;
            }

            if (array_key_exists('size', $row)) {
                $sponsor->setAttribute('size', $row['size']);
            }
        }

        return $sponsors;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Faq>
     */
    protected function hydrateFaqs(array $rows): Collection
    {
        $faqs = Faq::query()->hydrate($rows);
        $indexed = $faqs->values();

        foreach (array_values($rows) as $i => $row) {
            $faq = $indexed->get($i);
            if ($faq === null) {
                continue;
            }

            if (array_key_exists('group_name', $row)) {
                $faq->setAttribute('group_name', $row['group_name']);
            }

            if (array_key_exists('group_sort_order', $row)) {
                $faq->setAttribute('group_sort_order', $row['group_sort_order']);
            }
        }

        return $faqs;
    }

    /**
     * @return Collection<int, Partner>
     */
    protected function partners(?DonationEvent $event): Collection
    {
        if (! $event instanceof DonationEvent) {
            return collect();
        }

        return $event->partners()
            ->wherePivot('is_published', true)
            ->orderByPivot('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Sponsor>
     */
    protected function sponsors(?DonationEvent $event): Collection
    {
        if (! $event instanceof DonationEvent) {
            return collect();
        }

        $sponsors = $event->sponsors()
            ->wherePivot('is_published', true)
            ->orderByPivot('sort_order')
            ->orderBy('name')
            ->get();

        $sponsors->each(function (Sponsor $sponsor): void {
            $sponsor->setAttribute('size', $sponsor->pivot->getAttribute('size'));
        });

        return $sponsors;
    }

    /**
     * @return Collection<int, Faq>
     */
    protected function faqs(?DonationEvent $event): Collection
    {
        if ($event instanceof DonationEvent) {
            return $this->faqsForEvent($event);
        }

        return $this->globalFaqs();
    }

    /**
     * @return Collection<int, Faq>
     */
    protected function faqsForEvent(DonationEvent $event): Collection
    {
        $eventFaqs = $event->faqs()
            ->wherePivot('is_published', true)
            ->orderByPivot('sort_order')
            ->orderBy('title')
            ->get();

        $eventFaqs->each(function (Faq $faq): void {
            $faq->setAttribute('group_name', $faq->pivot->getAttribute('group') ?? 'general');
            $faq->setAttribute('group_sort_order', $faq->pivot->getAttribute('sort_order') ?? 9999);
        });

        $globalFaqs = $this->globalFaqs();

        $merged = $eventFaqs->merge($globalFaqs);

        return $merged
            ->sortBy([
                fn (Faq $faq) => $faq->group_name,
                fn (Faq $faq) => $faq->group_sort_order,
                fn (Faq $faq): string => strtolower($faq->title),
            ])
            ->values();
    }

    /**
     * @return Collection<int, Faq>
     */
    protected function globalFaqs(): Collection
    {
        return Faq::query()
            ->whereDoesntHave('donationEvents')
            ->orderBy('title')
            ->get()
            ->each(function (Faq $faq): void {
                $faq->setAttribute('group_name', 'general');
                $faq->setAttribute('group_sort_order', 9999);
            });
    }
}
