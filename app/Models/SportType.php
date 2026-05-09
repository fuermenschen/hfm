<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
])]
class SportType extends Model
{
    public function athletes()
    {
        return $this->hasMany(Athlete::class);
    }
}
