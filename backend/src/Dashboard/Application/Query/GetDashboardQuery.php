<?php

namespace App\Dashboard\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class GetDashboardQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
