<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Model;

final readonly class MenuView
{
    /**
     * @param list<MenuItemView>   $children
     * @param array<string, mixed> $attributes on the root list
     */
    public function __construct(
        public string $name,
        public array $children,
        public array $attributes = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->children;
    }
}
