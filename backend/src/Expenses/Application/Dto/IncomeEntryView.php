<?php

namespace App\Expenses\Application\Dto;

use App\Expenses\Domain\Model\IncomeEntry;

final readonly class IncomeEntryView
{
    public function __construct(
        public string $id,
        public ?string $sourceId,
        public ?string $memberId,
        public string $description,
        public float $amount,
        public string $currency,
        public string $receivedOn,
    ) {
    }

    public static function fromEntry(IncomeEntry $entry): self
    {
        return new self($entry->id(), $entry->sourceId(), $entry->memberId(), $entry->description(), $entry->amountCents() / 100, $entry->currency(), $entry->receivedOn()->format('Y-m-d'));
    }
}
