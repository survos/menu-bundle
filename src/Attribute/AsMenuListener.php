<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Attribute;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * #[AsMenuListener(MenuSlot::NAVBAR_END)] on a method that receives a MenuEvent.
 *
 * Exactly #[AsEventListener(event: ...)]: Symfony's attribute autoconfiguration looks up the class
 * hierarchy, so a subclass is registered with no extra wiring. It exists so a menu listener says what
 * it is, and so `grep AsMenuListener` finds every one.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class AsMenuListener extends AsEventListener
{
    public function __construct(string $menu, ?string $method = null, int $priority = 0)
    {
        parent::__construct(event: $menu, method: $method, priority: $priority);
    }
}
