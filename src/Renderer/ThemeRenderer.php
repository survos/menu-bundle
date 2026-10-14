<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Renderer;

use Knp\Menu\ItemInterface;
use Knp\Menu\Renderer\RendererInterface;
use Survos\MenuBundle\Model\MenuView;
use Twig\BlockChain;
use Twig\Environment;

/**
 * Draws a menu through a stack of Twig themes, the way form themes work.
 *
 * A theme is a template holding only the blocks it changes. The stack is searched highest first for
 * every block, including blocks one block calls with block(): an app theme with nothing but
 * {% block label %} restyles labels in a slot whose list, item and link come from the bundle theme
 * below it. Nothing extends anything. The bottom of every stack is @SurvosMenu/theme/base.html.twig.
 *
 * Registered with knp as the 'survos' renderer, so knp_menu_render(menu, {themes: [...]}, 'survos') works too.
 *
 * Options (all optional):
 *  - themes:          list of templates, highest precedence first (theme: one template, prepended)
 *  - rootAttributes:  attributes for the root list
 *  - currentClass, ancestorClass: classes for the current item and its parents ('active' by default)
 *  - depth, matchingDepth: as in knp
 *  - anything else reaches the theme as `options` (e.g. compact: true)
 */
final class ThemeRenderer implements RendererInterface
{
    public const BASE_THEME = '@SurvosMenu/theme/base.html.twig';

    private const DEFAULTS = [
        'themes' => [],
        'rootAttributes' => [],
        'currentClass' => 'active',
        'ancestorClass' => 'active',
        'depth' => null,
        'matchingDepth' => null,
    ];

    /** @var array<string, BlockChain> */
    private array $chains = [];

    /**
     * @param list<string> $baseThemes the bottom of every stack, lowest last
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly MenuViewFactory $views,
        private readonly array $baseThemes = [self::BASE_THEME],
    ) {
    }

    public function render(ItemInterface $item, array $options = []): string
    {
        $options = array_replace(self::DEFAULTS, $options);

        return $this->renderView($this->views->create($item, $options), $options);
    }

    /** @param array<string, mixed> $options */
    public function renderView(MenuView $menu, array $options = []): string
    {
        $options = array_replace(self::DEFAULTS, $options);
        if ($menu->isEmpty()) {
            return '';
        }

        $themes = [...(array) ($options['theme'] ?? []), ...$options['themes'], ...$this->baseThemes];
        unset($options['theme'], $options['themes']);

        return $this->chain($themes)->renderBlock('menu', ['menu' => $menu, 'options' => $options]);
    }

    /** @param list<string> $themes */
    private function chain(array $themes): BlockChain
    {
        $themes = array_values(array_unique($themes));

        return $this->chains[implode('|', $themes)] ??= new BlockChain($this->twig, $themes);
    }
}
