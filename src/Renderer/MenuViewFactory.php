<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Renderer;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Survos\MenuBundle\Model\Badge;
use Survos\MenuBundle\Model\ItemKind;
use Survos\MenuBundle\Model\MenuItemView;
use Survos\MenuBundle\Model\MenuView;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Turns a knp item tree into the MenuView a theme draws.
 *
 * This is the one place that knows the knp extras menus have been built with (icon, badge, submenu,
 * heading, divider, form, translation_domain, safe_label, data_attributes, tooltip) and the
 * divider_prepend/divider_append attributes. Themes never read them: they see a kind, a translated
 * label, a matched current/ancestor flag, and a tree with hidden items, empty submenus and stray
 * dividers already removed.
 */
final class MenuViewFactory
{
    /** Extras consumed here; whatever else a listener set reaches the theme as MenuItemView::$extras. */
    private const CONSUMED_EXTRAS = ['icon', 'badge', 'badges', 'submenu', 'is_submenu', 'heading', 'divider', 'form', 'translation_domain', 'safe_label', 'data_attributes', 'tooltip', 'routes', 'routesParameters'];

    private const DEFAULT_DOMAIN = 'messages';

    public function __construct(
        private readonly MatcherInterface $matcher,
        private readonly ?TranslatorInterface $translator = null,
    ) {
    }

    /**
     * @param array{depth?: ?int, matchingDepth?: ?int, rootAttributes?: array<string, mixed>} $options
     */
    public function create(ItemInterface $root, array $options = []): MenuView
    {
        $children = $root->getDisplayChildren() ? $this->children($root, 1, $options) : [];

        return new MenuView(
            (string) $root->getName(),
            $children,
            array_replace($root->getChildrenAttributes(), $options['rootAttributes'] ?? []),
        );
    }

    /** @return list<MenuItemView> */
    private function children(ItemInterface $parent, int $level, array $options): array
    {
        $views = [];
        foreach ($parent->getChildren() as $child) {
            if (!$child->isDisplayed()) {
                continue;
            }
            if ($child->getAttribute('divider_prepend')) {
                $views[] = $this->divider($child, $level, 'before');
            }
            if (null !== $view = $this->item($child, $level, $options)) {
                $views[] = $view;
            }
            if ($child->getAttribute('divider_append')) {
                $views[] = $this->divider($child, $level, 'after');
            }
        }

        return self::tidyDividers($views);
    }

    private function item(ItemInterface $item, int $level, array $options): ?MenuItemView
    {
        if ($item->getExtra('divider')) {
            return $this->divider($item, $level);
        }

        $maxDepth = $options['depth'] ?? null;
        $children = ($item->getDisplayChildren() && (null === $maxDepth || $level < $maxDepth))
            ? $this->children($item, $level + 1, $options)
            : [];
        $declaredSubmenu = (bool) ($item->getExtra('submenu') || $item->getExtra('is_submenu'));

        $kind = match (true) {
            [] !== $children => ItemKind::Submenu,
            $declaredSubmenu => null,                      // every child was filtered out: drop the dropdown
            (bool) $item->getExtra('heading') => ItemKind::Heading,
            (bool) $item->getExtra('form') => ItemKind::Search,
            default => ItemKind::Link,
        };
        if (null === $kind) {
            return null;
        }

        $attributes = $item->getAttributes();
        unset($attributes['divider_prepend'], $attributes['divider_append']);

        $linkAttributes = $item->getLinkAttributes();
        foreach ((array) $item->getExtra('data_attributes', []) as $name => $value) {
            $linkAttributes['data-'.$name] ??= $value;
        }

        $badge = Badge::fromMixed($item->getExtra('badge'));
        if (null === $badge && is_array($badges = $item->getExtra('badges')) && [] !== $badges) {
            $badge = Badge::fromMixed(reset($badges));
        }

        $tooltip = $item->getExtra('tooltip');

        return new MenuItemView(
            name: (string) $item->getName(),
            kind: $kind,
            label: $this->label($item),
            safeLabel: (bool) $item->getExtra('safe_label', false),
            uri: $item->getUri(),
            icon: is_string($icon = $item->getExtra('icon')) && '' !== $icon ? $icon : null,
            badge: $badge,
            tooltip: is_string($tooltip) && '' !== $tooltip ? $tooltip : null,
            current: (bool) $this->matcher->isCurrent($item),
            ancestor: $this->matcher->isAncestor($item, $options['matchingDepth'] ?? null),
            level: $level,
            attributes: $attributes,
            linkAttributes: $linkAttributes,
            children: $children,
            extras: array_diff_key($item->getExtras(), array_flip(self::CONSUMED_EXTRAS)),
        );
    }

    private function label(ItemInterface $item): string
    {
        $label = (string) $item->getLabel();
        $domain = $item->getExtra('translation_domain', self::DEFAULT_DOMAIN);
        if ('' === $label || false === $domain || null === $this->translator) {
            return $label;
        }

        return $this->translator->trans($label, [], is_string($domain) && '' !== $domain ? $domain : self::DEFAULT_DOMAIN);
    }

    private function divider(ItemInterface $item, int $level, string $suffix = ''): MenuItemView
    {
        return new MenuItemView(name: $item->getName().('' !== $suffix ? '.'.$suffix : ''), kind: ItemKind::Divider, level: $level);
    }

    /**
     * A divider separates two things: none at either end, never two in a row. Filtering items out
     * (a missing route, a denied #[IsGranted]) is what leaves them stranded.
     *
     * @param list<MenuItemView> $views
     *
     * @return list<MenuItemView>
     */
    private static function tidyDividers(array $views): array
    {
        $tidy = [];
        foreach ($views as $view) {
            if (ItemKind::Divider === $view->kind && ([] === $tidy || ItemKind::Divider === end($tidy)->kind)) {
                continue;
            }
            $tidy[] = $view;
        }
        while ([] !== $tidy && ItemKind::Divider === end($tidy)->kind) {
            array_pop($tidy);
        }

        return $tidy;
    }
}
