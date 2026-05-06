<?php

namespace App\Models;

use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Sponsor extends Model
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo_filename',
        'url',
    ];

    public function donationEvents(): BelongsToMany
    {
        return $this->belongsToMany(DonationEvent::class, 'donation_event_sponsor')
            ->withPivot(['size', 'contribution_text', 'sort_order', 'is_published'])
            ->withTimestamps();
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_filename === '') {
            return null;
        }

        return Storage::disk('public')->url('sponsors/'.$this->logo_filename);
    }
}
