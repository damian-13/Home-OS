<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\ExpenseBudget;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;

final readonly class UpdateBudgetsHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return UpdateBudgetsCommand::class; }
    public function __invoke(UpdateBudgetsCommand $command): void
    {
        foreach ($command->budgets as $row) {
            $categoryId = trim((string) ($row['categoryId'] ?? ''));
            if ($categoryId === '') {
                continue;
            }
            $amountCents = max(0, (int) round((float) ($row['amount'] ?? 0) * 100));
            $budget = $this->expenses->findBudget($command->householdId, $categoryId, $command->month);
            if (!$budget) {
                $budget = new ExpenseBudget((string) Uuid::new(), $command->householdId, $this->expenses->getCategory($command->householdId, $categoryId), $command->month, $amountCents);
            } else {
                $budget->changeAmount($amountCents);
            }
            $this->expenses->saveBudget($budget);
        }
    }
}
