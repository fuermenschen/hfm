<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\AdminFiles\AdminFileStorage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function donationEvents(): BelongsToMany
    {
        return $this->belongsToMany(DonationEvent::class, 'donation_event_partner')
            ->withPivot(['sort_order', 'is_published'])
            ->withTimestamps();
    }

    public function logoLightUrl(): ?string
    {
        return $this->logoUrl($this->logo_light_filename);
    }

    public function logoDarkUrl(): ?string
    {
        return $this->logoUrl($this->logo_dark_filename);
    }

    protected function logoUrl(string $filename): ?string
    {
        $path = $this->logoPath($filename);

        return $path === null ? null : Storage::disk('public')->url($path);
    }

    protected function logoPath(string $path): ?string
    {
        try {
            return resolve(AdminFileStorage::class)->normalizePath('partners/'.ltrim($path, '/'));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
