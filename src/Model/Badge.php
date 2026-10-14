<?php

declare(strict_types=1);

namespace Survos\MenuBundle\Model;

final readonly class Badge
{
    public function __construct(
        public string|int $value,
        public ?string $color = null,
    ) {
    }

    /** Accepts a Badge, any object with value/color properties (tabler's MenuBadge), ['value' => ..], or a scalar. */
    public static function fromMixed(mixed $badge): ?self
    {
        return match (true) {
            null === $badge, false === $badge, '' === $badge => null,
            $badge instanceof self => $badge,
            is_string($badge), is_int($badge) => new self($badge),
            is_array($badge) => isset($badge['value']) ? new self($badge['value'], is_string($badge['color'] ?? null) ? $badge['color'] : null) : null,
            is_object($badge) && isset($badge->value) => new self($badge->value, $badge->color ?? null),
            default => null,
        };
    }
}
