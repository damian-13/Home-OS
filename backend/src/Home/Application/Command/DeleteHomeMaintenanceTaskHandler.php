<?php

namespace App\Home\Application\Command;

use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class DeleteHomeMaintenanceTaskHandler implements CommandHandler
{
    public function __construct(
        private HomeMaintenanceRepository $tasks,
    ) {
    }

    public function handles(): string
    {
        return DeleteHomeMaintenanceTaskCommand::class;
    }

    public function __invoke(DeleteHomeMaintenanceTaskCommand $command): void
    {
        $task = $this->tasks->get($command->householdId, $command->taskId);
        $task->delete();

        $this->tasks->save($task);
    }
}
