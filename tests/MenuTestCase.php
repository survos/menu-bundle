<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Tests;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Matcher\Voter\UriVoter;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Survos\MenuBundle\Renderer\MenuViewFactory;
use Survos\MenuBundle\Renderer\ThemeRenderer;
use Survos\MenuBundle\Service\MenuDispatcher;
use Survos\MenuBundle\Service\Menus;
use Survos\MenuBundle\Service\MenuSpecProviderInterface;
use Survos\MenuBundle\Twig\MenuExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

abstract class MenuTestCase extends TestCase
{
    protected MenuFactory $factory;
    protected EventDispatcher $events;

    protected function setUp(): void
    {
        $this->factory = new MenuFactory();
        $this->events = new EventDispatcher();
    }

    protected function views(?string $currentUri = '/current'): MenuViewFactory
    {
        return new MenuViewFactory(new Matcher([new UriVoter($currentUri)]));
    }

    /**
     * A Twig environment with the bundle's templates and the given app templates.
     *
     * @param array<string, string>           $templates
     * @param list<MenuSpecProviderInterface> $specs
     *
     * @return array{Environment, Menus}
     */
    protected function stack(array $templates = [], array $specs = [], ?string $currentUri = '/current'): array
    {
        $files = new FilesystemLoader();
        $files->addPath(dirname(__DIR__).'/templates', 'SurvosMenu');
        $twig = new Environment(new ChainLoader([new ArrayLoader($templates), $files]), ['strict_variables' => true]);
        $views = $this->views($currentUri);
        $menus = new Menus(new MenuDispatcher($this->factory, $this->events), new ThemeRenderer($twig, $views), $views, $specs);
        $twig->addExtension(new MenuExtension($menus));

        return [$twig, $menus];
    }

    protected function root(): ItemInterface
    {
        return $this->factory->createItem('root');
    }

    /** Collapses whitespace between tags so assertions read like the markup. */
    protected static function squish(string $html): string
    {
        return trim((string) preg_replace('/>\s+</', '><', $html));
    }
}
