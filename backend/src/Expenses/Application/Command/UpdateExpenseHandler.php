<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class UpdateExpenseHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return UpdateExpenseCommand::class;
    }

    public function __invoke(UpdateExpenseCommand $command): string
    {
        if ($command->amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be greater than zero.');
        }

        $expense = $this->expenses->getExpense($command->householdId, $command->expenseId);
        $expense->changeDetails(
            $this->expenses->getCategory($command->householdId, $command->categoryId),
            trim($command->description),
            (int) round($command->amount * 100),
            new DateTimeImmutable($command->spentOn),
            $command->paidByMemberId,
        );

        if ($command->reviewStatus !== null) {
            $expense->changeReview($command->reviewStatus, $command->reviewReason);
        }

        $this->expenses->saveExpense($expense);

        return $expense->id();
    }
}
