<?php

declare(strict_types=1);

namespace App\Services\Webling\Letter\Dto;

/**
 * Immutable DTO representing page margins in points.
 */
class PageMargin
{
    public function __construct(
        public int $top = 95,
        public int $right = 78,
        public int $bottom = 95,
        public int $left = 95,
    ) {}

    /**
     * @return array{top:int,right:int,bottom:int,left:int}
     */
    public function toArray(): array
    {
        return [
            'top' => $this->top,
            'right' => $this->right,
            'bottom' => $this->bottom,
            'left' => $this->left,
        ];
    }
}
