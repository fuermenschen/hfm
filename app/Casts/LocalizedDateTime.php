<?php

declare(strict_types=1);

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class LocalizedDateTime implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $timezone = is_string($attributes['timezone'] ?? null)
            ? $attributes['timezone']
            : 'UTC';

        return Date::parse($value, 'UTC')->setTimezone($timezone);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [$key => null];
        }

        $timezone = is_string($attributes['timezone'] ?? null)
            ? $attributes['timezone']
            : 'UTC';

        if (! $value instanceof Carbon) {
            $value = Date::parse($value, $timezone);
        }

        return [$key => $value->clone()->setTimezone('UTC')->toDateTimeString()];
    }
}
