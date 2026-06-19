<?php

namespace App\Household\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class AddHouseholdMemberCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $displayName,
        public string $memberType,
        public ?string $birthDate,
        public ?string $color,
    ) {
    }
}
