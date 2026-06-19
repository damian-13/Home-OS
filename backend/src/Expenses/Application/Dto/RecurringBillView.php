<?php

namespace App\Expenses\Application\Dto;

use App\Expenses\Domain\Model\RecurringBill;

final readonly class RecurringBillView
{
    public function __construct(
        public string $id,
        public string $name,
        public float $amount,
        public string $currency,
        public int $dueDay,
        public ExpenseCategoryView $category,
        public ?string $paidByMemberId,
    ) {
    }

    public static function fromBill(RecurringBill $bill): self
    {
        return new self(
            $bill->id(),
            $bill->name(),
            $bill->amountCents() / 100,
            $bill->currency(),
            $bill->dueDay(),
            ExpenseCategoryView::fromCategory($bill->category()),
            $bill->paidByMemberId(),
        );
    }
}
