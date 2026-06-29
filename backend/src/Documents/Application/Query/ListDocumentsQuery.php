<?php

namespace App\Documents\Application\Query;

use App\Shared\Application\Query\Query;

final readonly class ListDocumentsQuery implements Query
{
    public function __construct(
        public string $householdId,
    ) {
    }
}
