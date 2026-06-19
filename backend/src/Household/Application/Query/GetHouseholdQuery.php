<?php

namespace App\Household\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class GetHouseholdQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
