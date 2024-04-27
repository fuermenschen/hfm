<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Athlete extends Model
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
        "sport_type_id",
        "partner_id",
        "age",
        "donation_token",
    ];

    public function sportType()
    {
        return $this->belongsTo(SportType::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
