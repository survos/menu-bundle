<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * <twig:menu name="NAVBAR_END" />, <twig:menu name="card.actions" :options="{card: card}" theme="@App/menu/card.html.twig" />.
 *
 * The same render path as {{ menu(...) }}. One difference: a component cannot see the calling template's
 * variables, so an entity the page renders reaches the listeners only when passed in :options (or when it is a
 * controller argument and a MenuOptionsResolverInterface puts it there).
 */
#[AsTwigComponent('menu', template: '@SurvosMenu/components/menu.html.twig')]
final class Menu
{
    /** the event name listeners populate: a MenuSlot constant or any custom name */
    public string $name = '';

    /** @var array<string, mixed> context for the listeners (MenuEvent::getOption()) */
    public array $options = [];

    /** @var array<string, mixed> ThemeRenderer options for this call */
    public array $render = [];

    /** a theme template stacked above the menu's own, e.g. '@App/menu/card.html.twig' */
    public ?string $theme = null;

    public function mount(string $name, ?string $theme = null): void
    {
        if ('' === $name) {
            throw new \LogicException('<twig:menu> needs a name="...": the event name your listeners populate.');
        }
        $this->name = $name;
        if (null !== $theme) {
            $this->render['theme'] = $theme;
        }
    }
}
