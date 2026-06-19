<?php

namespace App\Household\Application\Command;

use App\Household\Domain\Model\Household;
use App\Household\Domain\Repository\HouseholdRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;

final readonly class CreateHouseholdHandler implements CommandHandler
{
    public function __construct(
        private HouseholdRepository $households,
    ) {
    }

    public function handles(): string
    {
        return CreateHouseholdCommand::class;
    }

    public function __invoke(CreateHouseholdCommand $command): string
    {
        $household = new Household(
            (string) Uuid::new(),
            trim($command->name),
            strtoupper($command->defaultCurrency),
        );

        $this->households->save($household);

        return $household->id();
    }
}
