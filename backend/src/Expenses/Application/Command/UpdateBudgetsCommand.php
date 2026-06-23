<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateBudgetsCommand implements Command
{
    /**
     * @param list<array{categoryId?: string, amount?: float|int|string|null}> $budgets
     */
    public function __construct(
        public string $householdId,
        public string $month,
        public array $budgets,
    ) {
    }
}
