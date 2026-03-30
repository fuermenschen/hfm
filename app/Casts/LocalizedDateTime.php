<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

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

        return Carbon::parse($value, 'UTC')->setTimezone($timezone);
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
            $value = Carbon::parse($value, $timezone);
        }

        return [$key => $value->clone()->setTimezone('UTC')->toDateTimeString()];
    }
}
