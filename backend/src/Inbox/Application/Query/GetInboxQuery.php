<?php

namespace App\Inbox\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class GetInboxQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
