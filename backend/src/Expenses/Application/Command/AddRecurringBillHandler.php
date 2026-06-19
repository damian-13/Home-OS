<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\RecurringBill;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use InvalidArgumentException;

final readonly class AddRecurringBillHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return AddRecurringBillCommand::class;
    }

    public function __invoke(AddRecurringBillCommand $command): string
    {
        if ($command->amount <= 0) {
            throw new InvalidArgumentException('Bill amount must be greater than zero.');
        }

        if ($command->dueDay < 1 || $command->dueDay > 31) {
            throw new InvalidArgumentException('Bill due day must be between 1 and 31.');
        }

        $bill = new RecurringBill(
            (string) Uuid::new(),
            $command->householdId,
            $this->expenses->getCategory($command->householdId, $command->categoryId),
            trim($command->name),
            (int) round($command->amount * 100),
            'PLN',
            $command->dueDay,
            $command->paidByMemberId,
        );

        $this->expenses->saveRecurringBill($bill);

        return $bill->id();
    }
}
