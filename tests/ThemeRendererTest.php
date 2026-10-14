<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Tests;

use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\Service\MenuSpecProviderInterface;

final class ThemeRendererTest extends MenuTestCase
{
    private function fill(): void
    {
        $this->events->addListener('MAIN', static function (MenuEvent $e): void {
            $e->menu->addChild('home', ['uri' => '/current', 'label' => 'Home']);
            $more = $e->menu->addChild('more', ['label' => 'More']);
            $more->addChild('about', ['uri' => '/about', 'label' => 'About']);
        });
    }

    public function testTheBaseThemeDrawsPlainAccessibleMarkup(): void
    {
        $this->fill();
        [, $menus] = $this->stack();

        $html = self::squish($menus->render('MAIN', [], ['rootAttributes' => ['class' => 'nav']]));

        self::assertStringStartsWith('<ul class="menu nav">', $html);
        self::assertStringContainsString('<li class="menu-item active"><a href="/current" aria-current="page">Home</a></li>', $html);
        self::assertStringContainsString('<span class="menu-toggle">More</span><ul class="menu-children"><li class="menu-item"><a href="/about">About</a></li></ul>', $html);
    }

    public function testAThemeOverridesOneBlockForTheWholeTree(): void
    {
        $this->fill();
        [, $menus] = $this->stack(['shout.html.twig' => '{% block label %}{{ item.label|upper }}{% endblock %}']);

        $html = $menus->render('MAIN', [], ['theme' => 'shout.html.twig']);

        self::assertStringContainsString('>HOME</a>', $html);
        self::assertStringContainsString('>ABOUT</a>', $html, 'block("label") inside the base submenu resolves through the stack');
    }

    public function testThemesStackCallOverSpecOverBase(): void
    {
        $this->fill();
        $spec = new class implements MenuSpecProviderInterface {
            public function menuSpec(string $name): ?array
            {
                return 'MAIN' === $name ? ['theme' => 'slot.html.twig', 'currentClass' => 'is-here'] : null;
            }
        };
        [, $menus] = $this->stack([
            'slot.html.twig' => '{% block label %}[{{ item.label }}]{% endblock %}{% block badge %}!{% endblock %}',
            'call.html.twig' => '{% block label %}<{{ item.label }}>{% endblock %}',
        ], [$spec]);

        $html = $menus->render('MAIN', [], ['theme' => 'call.html.twig']);

        self::assertStringContainsString('<Home>!', $html, 'label from the call theme, badge from the slot theme');
        self::assertStringContainsString('class="menu-item is-here"', $html, 'slot spec options apply');
    }

    public function testAnEmptyMenuDrawsNothingAndHasNoItems(): void
    {
        $this->events->addListener('EMPTY', static function (MenuEvent $e): void {
            $e->menu->addChild('gated')->setExtra('submenu', true);
        });
        [$twig, $menus] = $this->stack(['page.html.twig' => '{{ menu_has_items("EMPTY") ? "yes" : "no" }}|{{ menu("EMPTY") }}']);

        self::assertFalse($menus->hasItems('EMPTY'));
        self::assertSame('no|', $twig->render('page.html.twig'));
    }

    public function testListenersSeeTheTemplatesObjects(): void
    {
        $this->events->addListener('PAGE_ACTIONS', static function (MenuEvent $e): void {
            $project = $e->getOption('project');
            $e->menu->addChild('edit', ['uri' => '/projects/'.$project->id.'/edit', 'label' => 'Edit']);
        });
        [$twig] = $this->stack(['page.html.twig' => '{{ menu(PAGE_ACTIONS) }}']);

        $html = $twig->render('page.html.twig', ['project' => (object) ['id' => 7], 'title' => 'not an object']);

        self::assertStringContainsString('href="/projects/7/edit"', $html);
    }

    public function testLabelsAreEscapedUnlessSafeAndAllowed(): void
    {
        $this->events->addListener('MAIN', static function (MenuEvent $e): void {
            $e->menu->addChild('x', ['uri' => '/x', 'label' => '<b>x</b>'])->setExtra('safe_label', true);
        });
        [, $menus] = $this->stack();

        self::assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $menus->render('MAIN'));
        self::assertStringContainsString('<b>x</b>', $menus->render('MAIN', [], ['allow_safe_labels' => true]));
    }
}
