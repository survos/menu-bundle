<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Twig;

use Survos\MenuBundle\MenuSlot;
use Survos\MenuBundle\Service\Menus;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Markup;
use Twig\TwigFunction;

final class MenuExtension extends AbstractExtension implements GlobalsInterface
{
    /** Context keys that are framework plumbing, never the entities a page is about. */
    private const NOT_AMBIENT = ['this', 'attributes', 'computed', 'outerScope', 'app'];

    public function __construct(
        private readonly Menus $menus,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu', $this->menu(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('menu_has_items', $this->hasItems(...), ['needs_context' => true]),
            new TwigFunction('menu_render', $this->menus->render(...), ['is_safe' => ['html']]),
            new TwigFunction('menu_attrs', self::attributes(...), ['is_safe' => ['html']]),
        ];
    }

    /** The slot names as globals: {{ menu(NAVBAR_END) }}. */
    public function getGlobals(): array
    {
        return MenuSlot::all();
    }

    /**
     * {{ menu('NAVBAR_END') }}, {{ menu(PAGE_ACTIONS, {project: project}) }}, {{ menu(SIDEBAR, {}, {theme: '@App/menu/sidebar.html.twig'}) }}.
     * Listeners also see the calling template's object variables: a page rendering `project` needs no menu code.
     *
     * @param array<string, mixed> $context
     */
    public function menu(array $context, string $name, array $options = [], array $render = []): string
    {
        return $this->menus->render($name, $options, $render, self::templateObjects($context));
    }

    /** @param array<string, mixed> $context */
    public function hasItems(array $context, string $name, array $options = []): bool
    {
        return $this->menus->hasItems($name, $options, self::templateObjects($context));
    }

    /**
     * Prints attributes for an element: {{ menu_attrs(item.linkAttributes, {href: item.uri, class: 'nav-link'}) }}.
     * The item's own attributes win over the defaults, except `class`, which is merged. `true` prints a bare
     * attribute, `false` and null print nothing, and a class may be a list (empty entries are dropped).
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $defaults
     */
    public static function attributes(array $attributes, array $defaults = []): string
    {
        $classes = [...self::classList($defaults['class'] ?? null), ...self::classList($attributes['class'] ?? null)];
        $merged = array_replace($defaults, $attributes);
        unset($merged['class']);
        if ([] !== $classes) {
            $merged = ['class' => implode(' ', array_unique($classes))] + $merged;
        }

        $html = '';
        foreach ($merged as $name => $value) {
            if (null === $value || false === $value || is_array($value) || (is_object($value) && !$value instanceof \Stringable)) {
                continue;
            }
            $name = htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= true === $value ? ' '.$name : sprintf(' %s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return $html;
    }

    /** @return list<string> */
    private static function classList(mixed $class): array
    {
        $list = is_array($class) ? $class : [$class];
        $out = [];
        foreach ($list as $entry) {
            if (is_string($entry) && '' !== trim($entry)) {
                array_push($out, ...preg_split('/\s+/', trim($entry)));
            }
        }

        return $out;
    }

    /**
     * Objects only: the entities a page is about. Scalars in a template are too ambiguous to be options,
     * and framework objects (app, closures, markup, component plumbing) are not entities.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, object>
     */
    private static function templateObjects(array $context): array
    {
        $objects = [];
        foreach ($context as $key => $value) {
            if (!is_string($key) || '' === $key || '_' === $key[0] || !is_object($value) || in_array($key, self::NOT_AMBIENT, true)) {
                continue;
            }
            if ($value instanceof \Closure || $value instanceof Markup) {
                continue;
            }
            $objects[$key] = $value;
        }

        return $objects;
    }
}
