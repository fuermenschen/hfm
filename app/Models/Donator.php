<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donator extends Model
{

    protected $fillable = [
        "first_name",
        "last_name",
        "address",
        "zip_code",
        "city",
        "phone_number",
        "email",
        "athlete_id",
        "amount_per_round",
        "amount_max",
        "amount_min",
        "email_verified_at",
        "comment",
    ];
}
