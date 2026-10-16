<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'uuid'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    // boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
        ];
    }

    /**
     * Passwordless auth: SessionGuard::userFromRecaller() rejects null passwords
     * (framework 13.31+), which breaks remember-me recall. A stable empty string
     * keeps recaller cookies valid without introducing passwords.
     *
     * TODO: remove once laravel/framework merges laravel/framework#62055 (tracks laravel/framework#62054),
     * then verify remember-me recall works again with a null password.
     */
    public function getAuthPassword(): string
    {
        return '';
    }
}
