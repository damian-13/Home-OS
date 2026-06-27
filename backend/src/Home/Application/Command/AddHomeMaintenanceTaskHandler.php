<?php

namespace App\Home\Application\Command;

use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;

final readonly class AddHomeMaintenanceTaskHandler implements CommandHandler
{
    public function __construct(
        private HomeMaintenanceRepository $tasks,
    ) {
    }

    public function handles(): string
    {
        return AddHomeMaintenanceTaskCommand::class;
    }

    public function __invoke(AddHomeMaintenanceTaskCommand $command): string
    {
        $id = (string) Uuid::new();
        $task = new HomeMaintenanceTask(
            $id,
            $command->householdId,
            $command->title,
            $command->area,
            new DateTimeImmutable($command->nextDueAt),
            $command->recurrenceType,
            $command->assignedMemberId,
            $command->priority,
            $command->notes,
        );

        $this->tasks->save($task);

        return $id;
    }
}
