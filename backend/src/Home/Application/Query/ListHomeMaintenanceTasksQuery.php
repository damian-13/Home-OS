<?php

namespace App\Home\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class ListHomeMaintenanceTasksQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
