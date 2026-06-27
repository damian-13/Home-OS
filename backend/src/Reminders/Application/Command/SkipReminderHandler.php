<?php

namespace App\Reminders\Application\Command;

use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;

final readonly class SkipReminderHandler implements CommandHandler
{
    public function __construct(
        private ReminderRepository $reminders,
    ) {
    }

    public function handles(): string
    {
        return SkipReminderCommand::class;
    }

    public function __invoke(SkipReminderCommand $command): void
    {
        $reminder = $this->reminders->get($command->householdId, $command->reminderId);
        $reminder->skip(new DateTimeImmutable());

        $this->reminders->save($reminder);
    }
}
