<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use InvalidArgumentException;

final readonly class UpdateRecurringBillHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return UpdateRecurringBillCommand::class;
    }

    public function __invoke(UpdateRecurringBillCommand $command): string
    {
        if ($command->amount <= 0) {
            throw new InvalidArgumentException('Bill amount must be greater than zero.');
        }

        if ($command->dueDay < 1 || $command->dueDay > 31) {
            throw new InvalidArgumentException('Bill due day must be between 1 and 31.');
        }

        $bill = $this->expenses->getRecurringBill($command->householdId, $command->billId);
        $bill->changeDetails(
            $this->expenses->getCategory($command->householdId, $command->categoryId),
            trim($command->name),
            (int) round($command->amount * 100),
            $command->dueDay,
            $command->paidByMemberId,
        );
        $this->expenses->saveRecurringBill($bill);

        return $bill->id();
    }
}
