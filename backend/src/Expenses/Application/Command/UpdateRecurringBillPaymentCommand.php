<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateRecurringBillPaymentCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $billId,
        public string $month,
        public string $status,
        public ?string $paidOn,
        public ?float $amount,
    ) {
    }
}
