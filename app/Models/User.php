<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // the attributes that are mass assignable.
    protected $fillable = ["name", "email", "uuid"];

    // The attributes that should be hidden for serialization.
    protected $hidden = ["remember_token"];

    // The attributes that should be cast to native types.
    protected $casts = [
        "uuid" => "string",
    ];

    // boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->uuid = (string)Str::uuid();
        });
    }
}
