<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class UndoLastFinanceReviewBatchHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}

    public function handles(): string
    {
        return UndoLastFinanceReviewBatchCommand::class;
    }

    /**
     * @return array{undoneCount: int}
     */
    public function __invoke(UndoLastFinanceReviewBatchCommand $command): array
    {
        $batch = $this->expenses->latestUndoableReviewBatch($command->householdId);
        if (!$batch) {
            return ['undoneCount' => 0];
        }

        $undoneCount = 0;
        foreach ($batch->items() as $item) {
            if ($batch->targetType() === 'expense') {
                $expense = $this->expenses->getExpense($command->householdId, (string) $item['id']);
                $expense->changeDetails(
                    $this->expenses->getCategory($command->householdId, (string) $item['categoryId']),
                    $expense->description(),
                    $expense->amountCents(),
                    $expense->spentOn(),
                    $expense->paidByMemberId(),
                );
                $expense->changeReview((string) $item['reviewStatus'], $item['reviewReason'] ?? null);
                $this->expenses->saveExpense($expense);
                $undoneCount++;
            }

            if ($batch->targetType() === 'income') {
                $entry = $this->expenses->getIncomeEntry($command->householdId, (string) $item['id']);
                $entry->changeClassification((string) $item['incomeKind'], (string) $item['reviewStatus'], $item['reviewReason'] ?? null);
                $this->expenses->saveIncomeEntry($entry);
                $undoneCount++;
            }
        }

        $batch->markUndone();
        $this->expenses->saveReviewBatch($batch);

        return ['undoneCount' => $undoneCount];
    }
}
