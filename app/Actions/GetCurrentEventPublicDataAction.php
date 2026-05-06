<?php

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

        return Cache::remember($cacheKey, now()->addMinute(), function () use ($event): array {
            $partners = $this->partners($event);
            $sponsors = $this->sponsors($event);
            $faqs = $this->faqs($event);

            return compact('partners', 'sponsors', 'faqs');
        });
    }

    /**
     * @return Collection<int, Partner>
     */
    protected function partners(?DonationEvent $event): Collection
    {
        if ($event === null) {
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
        if ($event === null) {
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
        if ($event !== null) {
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
                fn (Faq $faq) => strtolower($faq->title),
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
