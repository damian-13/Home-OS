<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class DeleteIncomeEntryCommand implements Command
{
    public function __construct(public string $householdId, public string $entryId)
    {
    }
}
