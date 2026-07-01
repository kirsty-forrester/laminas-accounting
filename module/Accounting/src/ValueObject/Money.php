<?php

namespace Accounting\ValueObject;

final class Money
{
    private function __construct(public readonly int $pennies) {}

    public static function fromDecimal(string $pounds): self
    {
        // "500.00" → 50000
        $pennies = (int) round(((float) $pounds) * 100);
        
        return new self($pennies);
    }

    public static function fromMinor(int $pennies): self
    {
        return new self($pennies);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(Money $other): self
    {
        return new self($this->pennies + $other->pennies);
    }

    public function subtract(Money $other): self
    {
        return new self($this->pennies - $other->pennies);
    }

    public function equals(Money $other): bool
    {
        return $this->pennies === $other->pennies;
    }

    public function format(): string
    {
        // 50000 → "£500.00"
        $sign   = $this->pennies < 0 ? '-' : '';
        $abs    = abs($this->pennies);
        $pounds = intdiv($abs, 100);
        $pence  = $abs % 100;

        return sprintf('%s£%d.%02d', $sign, $pounds, $pence);
    }
}