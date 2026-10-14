<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Service;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Survos\MenuBundle\Event\MenuEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Builds a named menu by dispatching its event, once per name and options per request.
 *
 * A layout asks "has items?" and then draws the same menu, and breadcrumbs read other menus too; without the memo
 * every listener ran two or more times per menu. Keyed on the request object, so a worker never serves one
 * request's menu to the next.
 */
class MenuDispatcher
{
    /** @var \WeakMap<Request, array<string, ItemInterface>> */
    private \WeakMap $built;

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ?MenuOptionsResolverInterface $optionsResolver = null,
        private readonly ?RequestStack $requests = null,
    ) {
        $this->built = new \WeakMap();
    }

    /**
     * @param array<string, mixed>  $options
     * @param array<string, object> $templateObjects
     */
    public function dispatch(string $name, array $options = [], array $templateObjects = []): ItemInterface
    {
        $options = null !== $this->optionsResolver
            ? $this->optionsResolver->resolve($options, $templateObjects)
            : array_merge($templateObjects, $options);

        $request = $this->requests?->getCurrentRequest();
        $key = $name.'|'.self::fingerprint($options);
        if (null !== $request && isset($this->built[$request][$key])) {
            return $this->built[$request][$key];
        }

        $menu = $this->build($name, $options);
        if (null !== $request) {
            $memo = $this->built[$request] ?? [];
            $memo[$key] = $menu;
            $this->built[$request] = $memo;
        }

        return $menu;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * The event listeners receive. A UI bundle with its own MenuEvent subclass (kept so listeners type-hinted
     * on it keep working) overrides this.
     *
     * @param array<string, mixed> $options
     */
    protected function createEvent(ItemInterface $menu, array $options): MenuEvent
    {
        return new MenuEvent($menu, $this->factory, $options);
    }

    /** @param array<string, mixed> $options */
    private function build(string $name, array $options): ItemInterface
    {
        $menu = $this->factory->createItem($options['name'] ?? $name);
        $menu->setDisplay(false); // the root is a container: only its children are drawn

        $this->dispatcher->dispatch($this->createEvent($menu, $options), $name);

        return $menu;
    }

    /** Objects count by identity, so two different entities never share a menu. */
    private static function fingerprint(mixed $value): string
    {
        $normalise = static function (mixed $v) use (&$normalise) {
            return match (true) {
                is_object($v) => $v::class.'#'.spl_object_id($v),
                is_array($v) => array_map($normalise, $v),
                is_scalar($v) || null === $v => $v,
                default => get_debug_type($v),
            };
        };

        return md5(json_encode($normalise($value), JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '');
    }
}
