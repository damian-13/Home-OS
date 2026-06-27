<?php

namespace App\Home\Application\Command;

use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;

final readonly class UpdateHomeMaintenanceTaskHandler implements CommandHandler
{
    public function __construct(
        private HomeMaintenanceRepository $tasks,
    ) {
    }

    public function handles(): string
    {
        return UpdateHomeMaintenanceTaskCommand::class;
    }

    public function __invoke(UpdateHomeMaintenanceTaskCommand $command): void
    {
        $task = $this->tasks->get($command->householdId, $command->taskId);
        $task->changeDetails(
            $command->title,
            $command->area,
            new DateTimeImmutable($command->nextDueAt),
            $command->recurrenceType,
            $command->assignedMemberId,
            $command->priority,
            $command->notes,
        );

        $this->tasks->save($task);
    }
}
