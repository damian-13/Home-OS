<?php

namespace App\Expenses\Application\Dto;

use App\Expenses\Domain\Model\Expense;

final readonly class ExpenseView
{
    public function __construct(
        public string $id,
        public string $description,
        public float $amount,
        public string $currency,
        public string $spentOn,
        public ExpenseCategoryView $category,
        public ?string $paidByMemberId,
    ) {
    }

    public static function fromExpense(Expense $expense): self
    {
        return new self(
            $expense->id(),
            $expense->description(),
            $expense->amountCents() / 100,
            $expense->currency(),
            $expense->spentOn()->format('Y-m-d'),
            ExpenseCategoryView::fromCategory($expense->category()),
            $expense->paidByMemberId(),
        );
    }
}
