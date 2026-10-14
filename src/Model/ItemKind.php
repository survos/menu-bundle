<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Model;

/**
 * What an item is, decided once in PHP (MenuViewFactory) instead of re-derived from extras in every template.
 * A theme draws a kind with the block of the same name: item_link, item_submenu, ...
 */
enum ItemKind: string
{
    case Link = 'link';
    case Submenu = 'submenu';
    case Heading = 'heading';
    case Divider = 'divider';
    case Search = 'search';
}
