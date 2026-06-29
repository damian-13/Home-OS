<?php

namespace App\Timeline\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class GetHouseholdTimelineQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
