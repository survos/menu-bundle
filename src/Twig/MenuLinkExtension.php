<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Twig;

use Survos\MenuBundle\Renderer\MenuViewFactory;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** Also registered by Tabler when using Knp without SurvosMenuBundle enabled. */
final class MenuLinkExtension extends AbstractExtension
{
    public function __construct(private readonly MenuViewFactory $views)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('menu_link_attributes', $this->views->linkAttributes(...))];
    }
}
