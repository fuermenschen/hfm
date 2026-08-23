<?php

declare(strict_types=1);

namespace App\Support\Pulse;

use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Pulse\Contracts\ResolvesUsers as ResolvesUsersContract;

class ResolvesUsers implements ResolvesUsersContract
{
    /**
     * The resolved users, keyed by their prefixed Pulse key.
     *
     * @var Collection<string, Authenticatable>
     */
    protected Collection $resolvedUsers;

    /**
     * Return a unique key identifying the user.
     */
    public function key(Authenticatable $user): int|string|null
    {
        if ($user instanceof ExternalUser) {
            return 'external:'.$user->getAuthIdentifier();
        }

        if ($user instanceof User) {
            return 'admin:'.$user->getAuthIdentifier();
        }

        return $user->getAuthIdentifier();
    }

    /**
     * Eager load the users with the given keys.
     *
     * @param  Collection<int, mixed>  $keys
     */
    public function load(Collection $keys): self
    {
        $adminIds = $keys
            ->filter(fn (int|string|null $key): bool => is_string($key) && str_starts_with($key, 'admin:'))
            ->map(fn (string $key): string => substr($key, strlen('admin:')));
        $externalIds = $keys
            ->filter(fn (int|string|null $key): bool => is_string($key) && str_starts_with($key, 'external:'))
            ->map(fn (string $key): string => substr($key, strlen('external:')));

        $this->resolvedUsers = collect()
            ->merge(
                User::query()->findMany($adminIds)->mapWithKeys(
                    fn (User $user): array => ['admin:'.$user->getKey() => $user]
                )
            )
            ->merge(
                ExternalUser::query()->findMany($externalIds)->mapWithKeys(
                    fn (ExternalUser $user): array => ['external:'.$user->getKey() => $user]
                )
            );

        return $this;
    }

    /**
     * Find the user with the given key.
     *
     * @return object{name: string, extra?: string, avatar?: string}
     */
    public function find(int|string|null $key): object
    {
        $user = $this->resolvedUsers[$key] ?? null;

        if ($user instanceof User) {
            return (object) [
                'name' => $user->name,
                'extra' => $user->email,
                'avatar' => sprintf('https://gravatar.com/avatar/%s?d=mp', hash('sha256', trim(strtolower((string) $user->email)))),
            ];
        }

        if ($user instanceof ExternalUser) {
            return (object) [
                'name' => $user->full_name,
                'extra' => $user->email,
                // Generic placeholder: external users' emails must not be hashed for Gravatar lookups.
                'avatar' => 'https://gravatar.com/avatar?d=mp',
            ];
        }

        return (object) [
            'name' => 'ID: '.$key,
            'extra' => '',
            'avatar' => 'https://gravatar.com/avatar?d=mp',
        ];
    }
}
