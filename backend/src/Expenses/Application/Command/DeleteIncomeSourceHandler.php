<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class DeleteIncomeSourceHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return DeleteIncomeSourceCommand::class; }
    public function __invoke(DeleteIncomeSourceCommand $command): void
    {
        $source = $this->expenses->getIncomeSource($command->householdId, $command->sourceId);
        $source->delete();
        $this->expenses->saveIncomeSource($source);
    }
}
