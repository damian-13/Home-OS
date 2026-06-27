<?php

namespace App\Reminders\Application\Command;

use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;

final readonly class UpdateReminderHandler implements CommandHandler
{
    public function __construct(
        private ReminderRepository $reminders,
    ) {
    }

    public function handles(): string
    {
        return UpdateReminderCommand::class;
    }

    public function __invoke(UpdateReminderCommand $command): void
    {
        $reminder = $this->reminders->get($command->householdId, $command->reminderId);
        $reminder->changeDetails(
            $command->title,
            $command->note,
            new DateTimeImmutable($command->dueAt),
            $command->recurrenceType,
            $command->relatedType,
            $command->relatedId,
            $command->priority,
        );

        $this->reminders->save($reminder);
    }
}
