<?php

namespace App\Search\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class SearchHouseholdQuery implements Query
{
    public function __construct(
        public string $householdId,
        public string $query,
    ) {
    }
}
