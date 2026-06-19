<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateRecurringBillCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $billId,
        public string $categoryId,
        public string $name,
        public float $amount,
        public int $dueDay,
        public ?string $paidByMemberId,
    ) {
    }
}
