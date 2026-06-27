<?php

namespace App\Reminders\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class ListRemindersQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
