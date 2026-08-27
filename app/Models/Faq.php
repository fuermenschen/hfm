<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $group_name
 * @property int $group_sort_order
 * @property Pivot $pivot
 */
#[Fillable([
    'title',
    'content_md',
])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    /** @return BelongsToMany<DonationEvent, $this, Pivot, 'pivot'> */
    public function donationEvents(): BelongsToMany
    {
        return $this->belongsToMany(DonationEvent::class, 'donation_event_faq')
            ->withPivot(['group', 'sort_order', 'is_published'])
            ->withTimestamps();
    }
}
