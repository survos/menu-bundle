<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Renderer;

use Knp\Menu\ItemInterface;

/** Optional, generic enrichment at rendering time, after item filtering. */
interface LinkAttributesDecoratorInterface
{
    /** @return array<string, mixed> additional link attributes */
    public function decorate(ItemInterface $item, string $label, array $options = []): array;
}
