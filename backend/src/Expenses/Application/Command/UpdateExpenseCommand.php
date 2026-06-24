<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateExpenseCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $expenseId,
        public string $categoryId,
        public string $description,
        public float $amount,
        public string $spentOn,
        public ?string $paidByMemberId,
        public ?string $reviewStatus = null,
        public ?string $reviewReason = null,
    ) {
    }
}
