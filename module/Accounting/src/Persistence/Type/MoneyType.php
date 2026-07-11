<?php

declare(strict_types=1);

namespace Accounting\Persistence\Type;

use Accounting\ValueObject\Money;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class MoneyType extends Type
{
    /** The name we'll reference from XML mappings: type="money". */
    public const NAME = 'money';

    /**
     * Underlying storage is a plain integer column (pennies), matching the
     * existing schema. Delegating to the platform so it's portable.
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /** DB to PHP: the stored int becomes a Money value object. */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromMinor((int) $value);
    }

    /** PHP to DB: a Money value object becomes its integer pennies. */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Money) {
            throw new \InvalidArgumentException('Expected ' . Money::class);
        }

        return $value->pennies;
    }

    /** Bind the parameter as an integer, not a string. */
    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }
}
