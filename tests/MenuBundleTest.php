<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Tests;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;
use Survos\MenuBundle\Service\MenuDispatcher;
use Survos\MenuBundle\Twig\MenuExtension;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\AttributeAutoconfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class MenuBundleTest extends TestCase
{
    public function testAsMenuListenerIsAutoconfiguredLikeAsEventListener(): void
    {
        $container = new ContainerBuilder();
        // what FrameworkBundle registers for #[AsEventListener]
        $container->registerAttributeForAutoconfiguration(AsEventListener::class, static function (ChildDefinition $definition, AsEventListener $attribute, \ReflectionClass|\ReflectionMethod $reflector): void {
            $tag = get_object_vars($attribute);
            if ($reflector instanceof \ReflectionMethod) {
                $tag['method'] = $reflector->getName();
            }
            $definition->addTag('kernel.event_listener', $tag);
        });
        $container->register('listener', NavbarListener::class)->setAutoconfigured(true);

        new AttributeAutoconfigurationPass()->process($container);
        new ResolveInstanceofConditionalsPass()->process($container);

        $tags = $container->getDefinition('listener')->getTag('kernel.event_listener');
        self::assertSame(MenuSlot::NAVBAR_END, $tags[0]['event']);
        self::assertSame('fill', $tags[0]['method']);
        self::assertSame(5, $tags[0]['priority']);
    }

    public function testSlotsAreStringConstantsAndTwigGlobals(): void
    {
        self::assertSame('NAVBAR_END', MenuSlot::all()['NAVBAR_END']);
        self::assertSame(MenuSlot::all(), new MenuExtension(new \ReflectionClass(\Survos\MenuBundle\Service\Menus::class)->newInstanceWithoutConstructor())->getGlobals());
    }

    public function testMenusAreBuiltOncePerNameAndOptionsPerRequest(): void
    {
        $calls = 0;
        $events = new EventDispatcher();
        $events->addListener('NAVBAR_MENU', static function (MenuEvent $e) use (&$calls): void {
            ++$calls;
            $e->getMenu()->addChild('x');
        });
        $stack = new RequestStack();
        $stack->push(new Request());
        $dispatcher = new MenuDispatcher(new MenuFactory(), $events, null, $stack);

        $paper = new \stdClass();
        $first = $dispatcher->dispatch('NAVBAR_MENU', ['paper' => $paper]);
        self::assertSame($first, $dispatcher->dispatch('NAVBAR_MENU', ['paper' => $paper]));
        self::assertSame(1, $calls);

        $dispatcher->dispatch('NAVBAR_MENU', ['paper' => new \stdClass()]);
        self::assertSame(2, $calls, 'a different entity is a different menu');

        $stack->push(new Request());
        $dispatcher->dispatch('NAVBAR_MENU', ['paper' => $paper]);
        self::assertSame(3, $calls, 'the next request never sees this request\'s menu');
    }
}

final class NavbarListener
{
    #[AsMenuListener(MenuSlot::NAVBAR_END, priority: 5)]
    public function fill(MenuEvent $event): void
    {
    }
}
