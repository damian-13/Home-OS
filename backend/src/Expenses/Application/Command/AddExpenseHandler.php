<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\Expense;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AddExpenseHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return AddExpenseCommand::class;
    }

    public function __invoke(AddExpenseCommand $command): string
    {
        if ($command->amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be greater than zero.');
        }

        $category = $this->expenses->getCategory($command->householdId, $command->categoryId);
        $expense = new Expense(
            (string) Uuid::new(),
            $command->householdId,
            $category,
            trim($command->description),
            (int) round($command->amount * 100),
            'PLN',
            new DateTimeImmutable($command->spentOn),
            $command->paidByMemberId,
        );

        $this->expenses->saveExpense($expense);

        return $expense->id();
    }
}
