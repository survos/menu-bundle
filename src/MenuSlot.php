<?php

declare(strict_types=1);

namespace Survos\MenuBundle;

/**
 * The page regions a layout draws menus into. Each constant is an event name: listeners fill it
 * (#[AsMenuListener(MenuSlot::NAVBAR_END)]), a layout draws it ({{ menu(NAVBAR_END) }}).
 *
 * Constants, not an enum, on purpose: the set is open. A menu attached to an element is just
 * another event name ('card.actions'), and an attribute argument has to be a constant expression,
 * which a string constant is and an enum case's ->value is only at the cost of noise.
 */
final class MenuSlot
{
    // Page level
    public const BANNER = 'BANNER';
    public const BREADCRUMB = 'BREADCRUMB';
    public const PAGE_NAV = 'PAGE_NAV';
    public const PAGE_ACTIONS = 'PAGE_ACTIONS';

    // Top navbar
    public const NAVBAR_PRIMARY = 'NAVBAR_PRIMARY';       // links right of the logo, same row
    public const NAVBAR_START = 'NAVBAR_START';           // between brand and right side
    public const NAVBAR_END = 'NAVBAR_END';               // right side tools
    public const NAVBAR_THEME = 'NAVBAR_THEME';
    public const NAVBAR_NOTIFICATIONS = 'NAVBAR_NOTIFICATIONS';
    public const NAVBAR_APPS = 'NAVBAR_APPS';
    public const NAVBAR_LANGUAGE = 'NAVBAR_LANGUAGE';
    public const AUTH = 'AUTH';
    public const SEARCH = 'SEARCH';

    // Secondary navbar
    public const NAVBAR_MENU = 'NAVBAR_MENU';
    public const NAVBAR_MENU_END = 'NAVBAR_MENU_END';

    // Admin navbar
    public const ADMIN_NAVBAR_MENU = 'ADMIN_NAVBAR_MENU';
    public const ADMIN_NAVBAR_MENU_END = 'ADMIN_NAVBAR_MENU_END';

    public const SIDEBAR = 'SIDEBAR';

    public const FOOTER = 'FOOTER';
    public const FOOTER_END = 'FOOTER_END';

    /** @return array<string, string> constant name => event name, for Twig globals and debug views */
    public static function all(): array
    {
        return new \ReflectionClass(self::class)->getConstants();
    }
}
