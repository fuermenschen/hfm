<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportType extends Model
{
    public function athletes()
    {
        return $this->hasMany(Athlete::class);
    }
}
