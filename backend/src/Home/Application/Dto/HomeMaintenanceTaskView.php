<?php

namespace App\Home\Application\Dto;

use App\Home\Domain\Model\HomeMaintenanceTask;

final readonly class HomeMaintenanceTaskView
{
    public function __construct(
        public string $id,
        public string $householdId,
        public string $title,
        public string $area,
        public string $nextDueAt,
        public string $recurrenceType,
        public ?string $assignedMemberId,
        public string $priority,
        public ?string $notes,
        public string $status,
        public string $createdAt,
        public ?string $completedAt,
    ) {
    }

    public static function fromTask(HomeMaintenanceTask $task): self
    {
        return new self(
            $task->id(),
            $task->householdId(),
            $task->title(),
            $task->area(),
            $task->nextDueAt()->format('Y-m-d'),
            $task->recurrenceType(),
            $task->assignedMemberId(),
            $task->priority(),
            $task->notes(),
            $task->status(),
            $task->createdAt()->format(DATE_ATOM),
            $task->completedAt()?->format(DATE_ATOM),
        );
    }
}
