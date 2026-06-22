<?php

namespace App\Health\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class GetMarkerHistoryQuery implements Query
{
    public function __construct(
        public string $householdId,
        public string $markerName,
        public ?string $memberId = null,
    ) {
    }
}
