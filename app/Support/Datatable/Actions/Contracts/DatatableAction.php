<?php

namespace App\Support\Datatable\Actions\Contracts;

interface DatatableAction
{
    public function key(): string;

    public function group(): string;

    public function label(): string;

    public function permission(): ?string;

    /**
     * @param  array<string, mixed>  $context
     */
    public function isVisible(array $context = []): bool;

    /**
     * @param  array<string, mixed>  $context
     */
    public function isEnabled(array $context = []): bool;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function resolve(array $context = []): ?array;
}
