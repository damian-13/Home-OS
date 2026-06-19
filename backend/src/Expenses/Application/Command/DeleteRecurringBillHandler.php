<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class DeleteRecurringBillHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return DeleteRecurringBillCommand::class;
    }

    public function __invoke(DeleteRecurringBillCommand $command): void
    {
        $bill = $this->expenses->getRecurringBill($command->householdId, $command->billId);
        $bill->delete();
        $this->expenses->saveRecurringBill($bill);
    }
}
