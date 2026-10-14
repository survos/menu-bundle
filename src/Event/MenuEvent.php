<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched once per menu name and options per request. Listeners add items to $menu; the root itself is never drawn.
 */
class MenuEvent extends Event
{
    /**
     * @param array<string, mixed> $options      what the page knows: entities, flags (getOption())
     * @param array<string, mixed> $childOptions
     */
    public function __construct(
        public readonly ItemInterface $menu,
        public readonly FactoryInterface $factory,
        public readonly array $options = [],
        public readonly array $childOptions = [],
    ) {
    }

    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
}
