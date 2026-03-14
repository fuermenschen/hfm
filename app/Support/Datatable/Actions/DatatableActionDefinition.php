<?php

namespace App\Support\Datatable\Actions;

use App\Support\Datatable\Actions\Contracts\DatatableAction;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DatatableActionDefinition implements DatatableAction
{
    /**
     * @param  Closure(array<string, mixed>):bool  $visibleWhen
     * @param  Closure(array<string, mixed>):bool  $enabledWhen
     * @param  Closure(array<string, mixed>):array<string, mixed>  $execute
     */
    public function __construct(
        protected string $key,
        protected string $group,
        protected string $label,
        protected Closure $execute,
        protected ?string $icon = null,
        protected ?string $variant = null,
        protected ?string $permission = null,
        protected ?Closure $visibleWhen = null,
        protected ?Closure $enabledWhen = null,
    ) {
        $this->visibleWhen ??= static fn (array $context): bool => true;
        $this->enabledWhen ??= static fn (array $context): bool => true;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function resolve(array $context = []): ?array
    {
        if (! $this->isAuthorized() || ! $this->isVisible($context)) {
            return null;
        }

        return array_merge(
            [
                'key' => $this->key(),
                'group' => $this->group(),
                'label' => $this->label(),
                'icon' => $this->icon,
                'variant' => $this->variant,
                'permission' => $this->permission(),
                'disabled' => ! $this->isEnabled($context),
            ],
            ($this->execute)($context),
        );
    }

    protected function isAuthorized(): bool
    {
        if ($this->permission === null) {
            return true;
        }

        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return Gate::forUser($user)->allows($this->permission);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function isVisible(array $context = []): bool
    {
        return ($this->visibleWhen)($context);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function permission(): ?string
    {
        return $this->permission;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function isEnabled(array $context = []): bool
    {
        return ($this->enabledWhen)($context);
    }
}
