<?php

declare(strict_types=1);

namespace Accounting\Hydrator\Strategy;

use BackedEnum;
use Laminas\Hydrator\Strategy\StrategyInterface;

/**
 * Converts between a backed enum instance (on the object) and its
 * scalar backing value (in the database row).
 */
class EnumStrategy implements StrategyInterface
{
    /** @param class-string<BackedEnum> $enumClass */
    public function __construct(private string $enumClass) {}

    /** Object -> storage: enum instance to its backing value. */
    public function extract($value, ?object $object = null)
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /** Storage -> object: backing value to enum instance. */
    public function hydrate($value, ?array $data = null)
    {
        if ($value === null || $value instanceof BackedEnum) {
            return $value;
        }

        return ($this->enumClass)::from($value);
    }
}
