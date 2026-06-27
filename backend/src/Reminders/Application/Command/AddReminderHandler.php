<?php

namespace App\Reminders\Application\Command;

use App\Reminders\Domain\Model\Reminder;
use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;

final readonly class AddReminderHandler implements CommandHandler
{
    public function __construct(
        private ReminderRepository $reminders,
    ) {
    }

    public function handles(): string
    {
        return AddReminderCommand::class;
    }

    public function __invoke(AddReminderCommand $command): string
    {
        $reminder = new Reminder(
            (string) Uuid::new(),
            $command->householdId,
            $command->title,
            $command->note,
            new DateTimeImmutable($command->dueAt),
            $command->recurrenceType,
            $command->relatedType,
            $command->relatedId,
            $command->priority,
        );

        $this->reminders->save($reminder);

        return $reminder->id();
    }
}
