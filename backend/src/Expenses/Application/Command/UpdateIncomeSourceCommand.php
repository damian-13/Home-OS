<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateIncomeSourceCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $sourceId,
        public ?string $memberId,
        public string $name,
        public float $amount,
        public bool $active,
    ) {
    }
}
