<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class AssociationMember extends Model
{
    /** @use HasFactory<\Database\Factories\AssociationMemberFactory> */
    use HasFactory;

    use Notifiable;

    protected $fillable = [
        'company_name',
        'first_name',
        'last_name',
        'address',
        'zip_code',
        'city',
        'email',
        'phone_number',
        'comment',
    ];
}
