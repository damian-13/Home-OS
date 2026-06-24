<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateIncomeEntryCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $entryId,
        public ?string $sourceId,
        public ?string $memberId,
        public string $description,
        public float $amount,
        public string $receivedOn,
        public ?string $incomeKind = null,
        public ?string $reviewStatus = null,
        public ?string $reviewReason = null,
    ) {
    }
}
