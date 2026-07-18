<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\AdminFiles\AdminFileStorage;
use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Storage;

/** @property-read Pivot|null $pivot */
#[Fillable([
    'name',
    'description',
    'logo_filename',
    'url',
])]
class Sponsor extends Model
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory;

    public function donationEvents(): BelongsToMany
    {
        return $this->belongsToMany(DonationEvent::class, 'donation_event_sponsor')
            ->withPivot(['size', 'contribution_text', 'sort_order', 'is_published'])
            ->withTimestamps();
    }

    public function logoUrl(): ?string
    {
        $path = $this->logoPath($this->logo_filename);

        return $path === null ? null : Storage::disk('public')->url($path);
    }

    protected function logoPath(string $path): ?string
    {
        try {
            return resolve(AdminFileStorage::class)->normalizePath('sponsors/'.ltrim($path, '/'));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
