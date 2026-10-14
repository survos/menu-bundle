<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Service;

/**
 * Decides what a menu's listeners see as options. Implement it to add page context (the controller's entities,
 * request attributes, configured defaults); without one, a menu gets the template's objects and the call's options.
 */
interface MenuOptionsResolverInterface
{
    /**
     * @param array<string, mixed>  $options         passed to this one menu call; they win
     * @param array<string, object> $templateObjects the calling template's object variables
     *
     * @return array<string, mixed>
     */
    public function resolve(array $options = [], array $templateObjects = []): array;
}
