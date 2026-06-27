<?php

namespace App\Reminders\Application\Command;

use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class DeleteReminderHandler implements CommandHandler
{
    public function __construct(
        private ReminderRepository $reminders,
    ) {
    }

    public function handles(): string
    {
        return DeleteReminderCommand::class;
    }

    public function __invoke(DeleteReminderCommand $command): void
    {
        $reminder = $this->reminders->get($command->householdId, $command->reminderId);
        $reminder->delete();

        $this->reminders->save($reminder);
    }
}
