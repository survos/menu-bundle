<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Tests;

use Survos\MenuBundle\Model\ItemKind;
use Survos\MenuBundle\Model\MenuItemView;

final class MenuViewFactoryTest extends MenuTestCase
{
    public function testKindsAreDecidedOnce(): void
    {
        $root = $this->root();
        $root->addChild('link', ['uri' => '/a']);
        $root->addChild('heading')->setExtra('heading', true);
        $root->addChild('search', ['uri' => '/search'])->setExtra('form', true);
        $root->addChild('parent')->addChild('child', ['uri' => '/b']);

        $kinds = array_map(static fn (MenuItemView $v) => $v->kind, $this->views()->create($root)->children);

        self::assertSame([ItemKind::Link, ItemKind::Heading, ItemKind::Search, ItemKind::Submenu], $kinds);
    }

    public function testASubmenuWhoseChildrenWereAllFilteredOutIsDropped(): void
    {
        $root = $this->root();
        $root->addChild('gated')->setExtra('submenu', true);   // add() skipped every child (missing route, denied)
        $root->addChild('hidden', ['uri' => '/h'])->setDisplay(false);

        self::assertTrue($this->views()->create($root)->isEmpty(), 'nothing left to draw');
    }

    public function testStrandedDividersAreRemoved(): void
    {
        $root = $this->root();
        $root->addChild('d0')->setExtra('divider', true);
        $root->addChild('a', ['uri' => '/a']);
        $root->addChild('d1')->setExtra('divider', true);
        $root->addChild('d2')->setExtra('divider', true);
        $root->addChild('b', ['uri' => '/b'])->setAttribute('divider_append', true);

        $kinds = array_map(static fn (MenuItemView $v) => $v->kind->value, $this->views()->create($root)->children);

        self::assertSame(['link', 'divider', 'link'], $kinds);
    }

    public function testDividerAttributesBecomeItemsAndLeaveTheAttributes(): void
    {
        $root = $this->root();
        $root->addChild('a', ['uri' => '/a']);
        $root->addChild('b', ['uri' => '/b'])->setAttribute('divider_prepend', true);

        [$a, $divider, $b] = $this->views()->create($root)->children;

        self::assertSame(ItemKind::Divider, $divider->kind);
        self::assertArrayNotHasKey('divider_prepend', $b->attributes);
    }

    public function testCurrentAndAncestorAreMatchedInPhp(): void
    {
        $root = $this->root();
        $parent = $root->addChild('parent');
        $parent->addChild('here', ['uri' => '/current']);
        $parent->addChild('there', ['uri' => '/elsewhere']);

        [$view] = $this->views('/current')->create($root)->children;

        self::assertTrue($view->ancestor);
        self::assertFalse($view->current);
        self::assertTrue($view->children[0]->current);
        self::assertFalse($view->children[1]->current);
    }

    public function testExtrasAreTyped(): void
    {
        $root = $this->root();
        $item = $root->addChild('a', ['uri' => '/a', 'label' => 'Inbox']);
        $item->setExtra('icon', 'tabler:inbox');
        $item->setExtra('badge', ['value' => 3, 'color' => 'red']);
        $item->setExtra('data_attributes', ['action' => 'click->x#y']);
        $item->setExtra('translation_domain', false);
        $item->setExtra('custom', 'kept');

        [$view] = $this->views()->create($root)->children;

        self::assertSame('tabler:inbox', $view->icon);
        self::assertSame(3, $view->badge?->value);
        self::assertSame('red', $view->badge?->color);
        self::assertSame('click->x#y', $view->linkAttributes['data-action']);
        self::assertSame(['custom' => 'kept'], $view->extras, 'consumed extras are not passed through');
    }

    public function testDepthLimitsTheTree(): void
    {
        $root = $this->root();
        $root->addChild('parent', ['uri' => '/p'])->addChild('child', ['uri' => '/c']);

        [$view] = $this->views()->create($root, ['depth' => 1])->children;

        self::assertSame(ItemKind::Link, $view->kind);
    }

    public function testLinkDecoratorsRunAfterFilteringAndRetainExistingAttributes(): void
    {
        $root = $this->root();
        $root->addChild('hidden')->setDisplay(false);
        $root->addChild('empty')->setExtra('submenu', true);
        $root->addChild('visible', ['label' => 'Public label', 'uri' => '/public'])
            ->setLinkAttribute('data-action', 'example#open')
            ->setExtra('data_attributes', ['context' => 'public']);
        $decorator = new class implements \Survos\MenuBundle\Renderer\LinkAttributesDecoratorInterface {
            public array $seen = [];

            public function decorate(\Knp\Menu\ItemInterface $item, string $label, array $options = []): array
            {
                $this->seen[] = [$item->getName(), $label];

                return ['data-explanation' => 'Public copy'];
            }
        };
        $factory = new \Survos\MenuBundle\Renderer\MenuViewFactory(new \Knp\Menu\Matcher\Matcher(), linkDecorators: [$decorator]);
        $view = $factory->create($root);
        self::assertSame([['visible', 'Public label']], $decorator->seen);
        self::assertSame(['data-action' => 'example#open', 'data-context' => 'public', 'data-explanation' => 'Public copy'], $view->children[0]->linkAttributes);
    }
}
