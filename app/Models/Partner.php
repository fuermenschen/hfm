<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
    ];

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }
}
