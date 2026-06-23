<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class DeleteIncomeEntryHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return DeleteIncomeEntryCommand::class; }
    public function __invoke(DeleteIncomeEntryCommand $command): void
    {
        $entry = $this->expenses->getIncomeEntry($command->householdId, $command->entryId);
        $entry->delete();
        $this->expenses->saveIncomeEntry($entry);
    }
}
