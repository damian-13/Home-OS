<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class DeleteExpenseHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return DeleteExpenseCommand::class;
    }

    public function __invoke(DeleteExpenseCommand $command): void
    {
        $expense = $this->expenses->getExpense($command->householdId, $command->expenseId);
        $expense->delete();
        $this->expenses->saveExpense($expense);
    }
}
