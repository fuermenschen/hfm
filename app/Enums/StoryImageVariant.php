<?php

declare(strict_types=1);

namespace App\Enums;

enum StoryImageVariant: string
{
    case Light = 'light';
    case Dark = 'dark';

    public function logoPath(): string
    {
        return resource_path('images/logo_'.$this->value.'.svg');
    }

    public function backgroundColor(): string
    {
        return match ($this) {
            self::Light => '#f8fafc',
            self::Dark => '#1b2e47',
        };
    }

    public function textColor(): string
    {
        return match ($this) {
            self::Light => '#1b2e47',
            self::Dark => '#f8fafc',
        };
    }
}
