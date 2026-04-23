<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'content_md',
    ];

    public function donationEvents(): BelongsToMany
    {
        return $this->belongsToMany(DonationEvent::class, 'donation_event_faq')
            ->withPivot(['group', 'sort_order', 'is_published'])
            ->withTimestamps();
    }
}
