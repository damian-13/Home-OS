<?php

namespace App\Health\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class DeleteBloodTestCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $bloodTestId,
    ) {
    }
}
