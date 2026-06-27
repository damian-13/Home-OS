<?php

namespace App\Reminders\Application\Command;

use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;

final readonly class CompleteReminderHandler implements CommandHandler
{
    public function __construct(
        private ReminderRepository $reminders,
    ) {
    }

    public function handles(): string
    {
        return CompleteReminderCommand::class;
    }

    public function __invoke(CompleteReminderCommand $command): void
    {
        $reminder = $this->reminders->get($command->householdId, $command->reminderId);
        $reminder->complete(new DateTimeImmutable());

        $this->reminders->save($reminder);
    }
}
