<?php

declare(strict_types=1);

namespace App\ValueObjects;

readonly class Money
{
    public function __construct(
        public float $amount,
        public string $currency = 'BDT'
    ) {}

    public static function fromFloat(float $amount): self
    {
        return new self($amount);
    }

    public function formatted(): string
    {
        return '৳' . number_format($this->amount, 2);
    }

    public function add(Money $other): Money
    {
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        return new self(max(0, $this->amount - $other->amount), $this->currency);
    }

    public function isZero(): bool
    {
        return abs($this->amount) < 0.01;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function __toString(): string
    {
        return $this->formatted();
    }
}
