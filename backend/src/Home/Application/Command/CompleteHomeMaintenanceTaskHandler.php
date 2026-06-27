<?php

namespace App\Home\Application\Command;

use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;

final readonly class CompleteHomeMaintenanceTaskHandler implements CommandHandler
{
    public function __construct(
        private HomeMaintenanceRepository $tasks,
    ) {
    }

    public function handles(): string
    {
        return CompleteHomeMaintenanceTaskCommand::class;
    }

    public function __invoke(CompleteHomeMaintenanceTaskCommand $command): void
    {
        $task = $this->tasks->get($command->householdId, $command->taskId);
        $task->complete(new DateTimeImmutable());

        $this->tasks->save($task);
    }
}
