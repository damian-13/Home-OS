<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class AddIncomeSourceCommand implements Command
{
    public function __construct(
        public string $householdId,
        public ?string $memberId,
        public string $name,
        public float $amount,
    ) {
    }
}
