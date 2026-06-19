<?php

namespace App\Expenses\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class GetExpenseOverviewQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
