<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\FinanceReviewBatch;
use App\Expenses\Domain\Model\FinanceReviewRule;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ApplyFinanceReviewRuleHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}

    public function handles(): string
    {
        return ApplyFinanceReviewRuleCommand::class;
    }

    /**
     * @return array{id: string, appliedCount: int}
     */
    public function __invoke(ApplyFinanceReviewRuleCommand $command): array
    {
        $matchText = trim($command->matchText);
        if ($matchText === '' || mb_strlen($matchText) < 2) {
            throw new InvalidArgumentException('Rule match text must have at least two characters.');
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $command->month)) {
            throw new InvalidArgumentException('Rule month must use YYYY-MM format.');
        }

        $categoryId = $command->targetType === 'expense' ? $command->categoryId : null;
        $incomeKind = $command->targetType === 'income' ? $command->incomeKind : null;
        $rule = new FinanceReviewRule((string) Uuid::new(), $command->householdId, $command->targetType, $matchText, $categoryId, $incomeKind);
        $monthStart = new DateTimeImmutable($command->month . '-01 00:00:00');
        $monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);
        $appliedCount = 0;
        $batchItems = [];

        if ($command->targetType === 'expense') {
            $category = $this->expenses->getCategory($command->householdId, (string) $categoryId);
            foreach ($this->expenses->expensesBetween($command->householdId, $monthStart, $monthEnd) as $expense) {
                if ($expense->reviewStatus() !== 'needs_review' || !$this->matches($expense->description(), $matchText)) {
                    continue;
                }

                $batchItems[] = [
                    'id' => $expense->id(),
                    'categoryId' => $expense->category()->id(),
                    'reviewStatus' => $expense->reviewStatus(),
                    'reviewReason' => $expense->reviewReason(),
                ];
                $expense->changeDetails($category, $expense->description(), $expense->amountCents(), $expense->spentOn(), $expense->paidByMemberId());
                $expense->changeReview('reviewed');
                $this->expenses->saveExpense($expense);
                $appliedCount++;
            }
        } elseif ($command->targetType === 'income') {
            foreach ($this->expenses->incomeEntriesBetween($command->householdId, $monthStart, $monthEnd) as $entry) {
                if ($entry->reviewStatus() !== 'needs_review' || !$this->matches($entry->description(), $matchText)) {
                    continue;
                }

                $batchItems[] = [
                    'id' => $entry->id(),
                    'incomeKind' => $entry->incomeKind(),
                    'reviewStatus' => $entry->reviewStatus(),
                    'reviewReason' => $entry->reviewReason(),
                ];
                $entry->changeClassification((string) $incomeKind, 'reviewed');
                $this->expenses->saveIncomeEntry($entry);
                $appliedCount++;
            }
        } else {
            throw new InvalidArgumentException('Unsupported finance review rule target type.');
        }

        $rule->markApplied();
        $this->expenses->saveReviewRule($rule);
        if ($batchItems !== []) {
            $this->expenses->saveReviewBatch(new FinanceReviewBatch((string) Uuid::new(), $command->householdId, $rule->id(), $command->targetType, $matchText, $batchItems));
        }

        return ['id' => $rule->id(), 'appliedCount' => $appliedCount];
    }

    private function matches(string $description, string $matchText): bool
    {
        return mb_stripos($description, $matchText) !== false;
    }
}
