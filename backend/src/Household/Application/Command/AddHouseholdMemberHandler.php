<?php

namespace App\Household\Application\Command;

use App\Household\Domain\Repository\HouseholdRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;

final readonly class AddHouseholdMemberHandler implements CommandHandler
{
    public function __construct(
        private HouseholdRepository $households,
    ) {
    }

    public function handles(): string
    {
        return AddHouseholdMemberCommand::class;
    }

    public function __invoke(AddHouseholdMemberCommand $command): string
    {
        $household = $this->households->get($command->householdId);
        $member = $household->addMember(
            (string) Uuid::new(),
            trim($command->displayName),
            $command->memberType,
            $command->birthDate ? new DateTimeImmutable($command->birthDate) : null,
            $command->color ? trim($command->color) : null,
        );

        $this->households->save($household);

        return $member->id();
    }
}
