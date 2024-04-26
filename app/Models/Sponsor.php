<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;

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
