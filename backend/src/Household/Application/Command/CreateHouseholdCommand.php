<?php

namespace App\Household\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class CreateHouseholdCommand implements Command
{
    public function __construct(
        public string $name,
        public string $defaultCurrency = 'PLN',
    ) {
    }
}
