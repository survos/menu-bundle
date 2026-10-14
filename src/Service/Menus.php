<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Service;

use Knp\Menu\ItemInterface;
use Survos\MenuBundle\Model\MenuView;
use Survos\MenuBundle\Renderer\MenuViewFactory;
use Survos\MenuBundle\Renderer\ThemeRenderer;

/**
 * The one way to draw a named menu: build it (MenuDispatcher), look up how it is drawn (the spec providers),
 * render it (ThemeRenderer). menu(), <twig:menu> and PHP callers all come through here.
 */
final class Menus
{
    /** @param iterable<MenuSpecProviderInterface> $specProviders */
    public function __construct(
        private readonly MenuDispatcher $dispatcher,
        private readonly ThemeRenderer $renderer,
        private readonly MenuViewFactory $views,
        private readonly iterable $specProviders = [],
    ) {
    }

    /**
     * @param array<string, mixed>  $options         for the listeners (MenuEvent::getOption())
     * @param array<string, mixed>  $render          ThemeRenderer options for this call; `theme`/`themes` stack above the spec's
     * @param array<string, object> $templateObjects
     */
    public function render(string $name, array $options = [], array $render = [], array $templateObjects = []): string
    {
        $render = $this->renderOptions($name, $render);

        return $this->renderer->renderView($this->view($name, $options, $render, $templateObjects), $render);
    }

    /** @param array<string, object> $templateObjects */
    public function hasItems(string $name, array $options = [], array $templateObjects = []): bool
    {
        return !$this->view($name, $options, $this->renderOptions($name, []), $templateObjects)->isEmpty();
    }

    /** @param array<string, object> $templateObjects */
    public function getMenu(string $name, array $options = [], array $templateObjects = []): ItemInterface
    {
        return $this->dispatcher->dispatch($name, $options, $templateObjects);
    }

    /** @return array<string, mixed> the spec for $name with this call's overrides on top */
    public function renderOptions(string $name, array $render = []): array
    {
        $spec = [];
        foreach ($this->specProviders as $provider) {
            if (null !== $found = $provider->menuSpec($name)) {
                $spec = $found;
                break;
            }
        }

        $themes = [...(array) ($render['theme'] ?? []), ...($render['themes'] ?? []), ...(array) ($spec['theme'] ?? []), ...($spec['themes'] ?? [])];
        unset($spec['theme'], $render['theme']);

        return array_replace($spec, $render, ['themes' => $themes]);
    }

    /**
     * "Has items" means "has something to draw": a menu whose only children are a dropdown with every entry
     * filtered out is empty, which a raw hasChildren() on the knp tree gets wrong.
     *
     * @param array<string, object> $templateObjects
     */
    private function view(string $name, array $options, array $render, array $templateObjects): MenuView
    {
        return $this->views->create($this->dispatcher->dispatch($name, $options, $templateObjects), $render);
    }
}
