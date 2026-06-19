<?php

namespace App\Household\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class ListHouseholdMembersQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
