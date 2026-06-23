<?php

namespace App\Expenses\Application\Dto;

use App\Expenses\Domain\Model\IncomeSource;

final readonly class IncomeSourceView
{
    public function __construct(
        public string $id,
        public ?string $memberId,
        public string $name,
        public float $amount,
        public string $currency,
        public bool $active,
    ) {
    }

    public static function fromSource(IncomeSource $source): self
    {
        return new self($source->id(), $source->memberId(), $source->name(), $source->amountCents() / 100, $source->currency(), $source->active());
    }
}
