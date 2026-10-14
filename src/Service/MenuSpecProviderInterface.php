<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Service;

/**
 * How a named menu is drawn by default: its themes and render options. A UI bundle (tabler) implements this
 * for the page slots it lays out. Autoconfigured; the first provider with an answer wins.
 */
interface MenuSpecProviderInterface
{
    /** @return array<string, mixed>|null ThemeRenderer options, or null when this provider does not draw $name */
    public function menuSpec(string $name): ?array;
}
