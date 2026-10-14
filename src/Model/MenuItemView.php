<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Model;

/**
 * One item as a theme sees it: everything resolved (label translated, current/ancestor matched, empty
 * submenus and stray dividers pruned), so a theme block only prints.
 */
final readonly class MenuItemView
{
    /**
     * @param array<string, mixed> $attributes     on the wrapper (<li>)
     * @param array<string, mixed> $linkAttributes on the <a>
     * @param list<self>           $children
     * @param array<string, mixed> $extras         the item's remaining knp extras, for theme-specific needs
     */
    public function __construct(
        public string $name,
        public ItemKind $kind,
        public string $label = '',
        public bool $safeLabel = false,
        public ?string $uri = null,
        public ?string $icon = null,
        public ?Badge $badge = null,
        public ?string $tooltip = null,
        public bool $current = false,
        public bool $ancestor = false,
        public int $level = 1,
        public array $attributes = [],
        public array $linkAttributes = [],
        public array $children = [],
        public array $extras = [],
    ) {
    }

    public function hasChildren(): bool
    {
        return [] !== $this->children;
    }

    /** current, or the parent of the current page */
    public function isActive(): bool
    {
        return $this->current || $this->ancestor;
    }

    public function extra(string $name, mixed $default = null): mixed
    {
        return $this->extras[$name] ?? $default;
    }
}
