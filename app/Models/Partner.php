<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'logo_light_filename',
    'logo_dark_filename',
    'beneficiary_blurb',
    'url',
])]
class Partner extends Model
{
    use HasFactory;

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function donationEvents(): BelongsToMany
    {
        return $this->belongsToMany(DonationEvent::class, 'donation_event_partner')
            ->withPivot(['sort_order', 'is_published'])
            ->withTimestamps();
    }

    public function shouldDisplayLogo(): bool
    {
        return is_string($this->logo_light_filename)
            && $this->logo_light_filename !== ''
            && is_string($this->logo_dark_filename)
            && $this->logo_dark_filename !== '';
    }

    public function logoLightUrl(): ?string
    {
        if (! is_string($this->logo_light_filename) || $this->logo_light_filename === '') {
            return null;
        }

        return Storage::disk('public')->url('partners/'.$this->logo_light_filename);
    }

    public function logoDarkUrl(): ?string
    {
        if (! is_string($this->logo_dark_filename) || $this->logo_dark_filename === '') {
            return null;
        }

        return Storage::disk('public')->url('partners/'.$this->logo_dark_filename);
    }
}
