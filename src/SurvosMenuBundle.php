<?php

declare(strict_types=1);

namespace Survos\MenuBundle;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Survos\Kit\AbstractSurvosBundle;
use Survos\Kit\SurvosKitBundle;
use Survos\MenuBundle\Renderer\MenuViewFactory;
use Survos\MenuBundle\Renderer\ThemeRenderer;
use Survos\MenuBundle\Service\MenuDispatcher;
use Survos\MenuBundle\Service\MenuOptionsResolverInterface;
use Survos\MenuBundle\Service\Menus;
use Survos\MenuBundle\Service\MenuSpecProviderInterface;
use Survos\MenuBundle\Twig\Components\Menu;
use Survos\MenuBundle\Twig\MenuExtension;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Menus on top of knplabs/knp-menu. The knp item tree, factory and matcher are used through their interfaces
 * only (FactoryInterface, MatcherInterface), never through knp-menu-bundle's service ids or classes, so the
 * bundle that provides them can be swapped out later.
 */
#[RequiredBundle(SurvosKitBundle::class)]
// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosMenuBundle extends AbstractSurvosBundle implements CompilerPassInterface
{
    public const SPEC_PROVIDER_TAG = 'survos_menu.spec_provider';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass($this);
    }

    /** @param array<mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);

        $builder->registerForAutoconfiguration(MenuSpecProviderInterface::class)->addTag(self::SPEC_PROVIDER_TAG);

        $builder->register(MenuViewFactory::class)
            ->setArgument('$matcher', new Reference(MatcherInterface::class))
            ->setArgument('$translator', new Reference('translator', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        $builder->register(ThemeRenderer::class)
            ->setArgument('$twig', new Reference('twig'))
            ->setArgument('$views', new Reference(MenuViewFactory::class))
            ->addTag('knp_menu.renderer', ['alias' => 'survos']);

        $builder->register(MenuDispatcher::class)
            ->setArgument('$factory', new Reference(FactoryInterface::class))
            ->setArgument('$dispatcher', new Reference('event_dispatcher'))
            ->setArgument('$optionsResolver', new Reference(MenuOptionsResolverInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setArgument('$requests', new Reference('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        $builder->register(Menus::class)
            ->setPublic(true)
            ->setArgument('$dispatcher', new Reference(MenuDispatcher::class))
            ->setArgument('$renderer', new Reference(ThemeRenderer::class))
            ->setArgument('$views', new Reference(MenuViewFactory::class))
            ->setArgument('$specProviders', new TaggedIteratorArgument(self::SPEC_PROVIDER_TAG));

        $builder->register(MenuExtension::class)
            ->setArgument('$menus', new Reference(Menus::class))
            ->addTag('twig.extension');

        $builder->register(Menu::class)
            ->setAutoconfigured(true);
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ([FactoryInterface::class, MatcherInterface::class] as $id) {
            if (!$container->has($id)) {
                throw new \LogicException(sprintf('survos/menu-bundle needs a "%s" service. Register KnpMenuBundle (knplabs/knp-menu-bundle) in config/bundles.php, or alias the interface to your own implementation.', $id));
            }
        }
    }
}
