<?php

namespace App\Health\Application\Command;

use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Command\CommandHandler;
use InvalidArgumentException;

final readonly class DeleteBloodTestHandler implements CommandHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return DeleteBloodTestCommand::class;
    }

    public function __invoke(DeleteBloodTestCommand $command): void
    {
        $bloodTest = $this->health->bloodTestById($command->householdId, $command->bloodTestId);

        if (!$bloodTest) {
            throw new InvalidArgumentException('Blood test not found.');
        }

        $bloodTest->delete();
        $this->health->saveBloodTest($bloodTest);
    }
}
