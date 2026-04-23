<?php

namespace App\Models;

use App\Casts\LocalizedDateTime;
use Database\Factories\DonationEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class DonationEvent extends Model
{
    /** @use HasFactory<DonationEventFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'timezone',
        'starts_at',
        'ends_at',
        'registration_opens_at',
        'athlete_registration_closes_at',
        'donor_registration_closes_at',
        'location_name',
        'location_street',
        'location_postal_code',
        'location_city',
        'location_url',
        'is_published',
        'has_equal_split_option',
        'content',
    ];

    protected $casts = [
        'starts_at' => LocalizedDateTime::class,
        'ends_at' => LocalizedDateTime::class,
        'registration_opens_at' => LocalizedDateTime::class,
        'athlete_registration_closes_at' => LocalizedDateTime::class,
        'donor_registration_closes_at' => LocalizedDateTime::class,
        'is_published' => 'boolean',
        'has_equal_split_option' => 'boolean',
        'content' => 'array',
    ];

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'donation_event_partner')
            ->withPivot(['sort_order', 'is_published'])
            ->withTimestamps();
    }

    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class, 'donation_event_sponsor')
            ->withPivot(['size', 'contribution_text', 'sort_order', 'is_published'])
            ->withTimestamps();
    }

    public function faqs(): BelongsToMany
    {
        return $this->belongsToMany(Faq::class, 'donation_event_faq')
            ->withPivot(['group', 'sort_order', 'is_published'])
            ->withTimestamps();
    }

    public function contentValue(string $path, ?string $default = null): ?string
    {
        $value = data_get($this->content ?? [], $path, $default);

        if (! is_string($value)) {
            return $default;
        }

        return $value;
    }

    public function contentMarkdown(string $path, ?string $default = null): HtmlString
    {
        $markdown = $this->contentValue($path, $default) ?? '';

        return new HtmlString(Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    public function contentInlineMarkdown(string $path, ?string $default = null): HtmlString
    {
        $markdown = $this->contentValue($path, $default) ?? '';

        return new HtmlString(Str::inlineMarkdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    public function contentPlainText(string $path, ?string $default = null): string
    {
        return trim(strip_tags((string) $this->contentMarkdown($path, $default)));
    }
}
