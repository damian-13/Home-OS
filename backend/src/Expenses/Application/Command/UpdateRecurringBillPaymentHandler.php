<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\RecurringBillPayment;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;

final readonly class UpdateRecurringBillPaymentHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return UpdateRecurringBillPaymentCommand::class; }
    public function __invoke(UpdateRecurringBillPaymentCommand $command): string
    {
        $bill = $this->expenses->getRecurringBill($command->householdId, $command->billId);
        $payment = $this->expenses->findRecurringBillPayment($command->householdId, $command->billId, $command->month);
        $paidOn = $command->paidOn ? new DateTimeImmutable($command->paidOn) : null;
        $amountCents = $command->amount === null ? null : max(0, (int) round($command->amount * 100));
        if (!$payment) {
            $payment = new RecurringBillPayment((string) Uuid::new(), $command->householdId, $bill->id(), $command->month, $command->status, $paidOn, $amountCents);
        } else {
            $payment->changeDetails($command->status, $paidOn, $amountCents);
        }
        $this->expenses->saveRecurringBillPayment($payment);
        return $payment->id();
    }
}
