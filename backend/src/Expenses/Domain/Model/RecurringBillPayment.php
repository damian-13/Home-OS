<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'recurring_bill_payments')]
#[ORM\UniqueConstraint(name: 'UNIQ_RECURRING_BILL_PAYMENT_MONTH', columns: ['recurring_bill_id', 'payment_month'])]
class RecurringBillPayment
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'recurring_bill_id', type: 'string', length: 36)]
    private string $billId;

    #[ORM\Column(name: 'household_id', type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(name: 'payment_month', type: 'string', length: 7)]
    private string $month;

    #[ORM\Column(type: 'string', length: 16)]
    private string $status;

    #[ORM\Column(name: 'paid_on', type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $paidOn;

    #[ORM\Column(name: 'amount_override_cents', type: 'integer', nullable: true)]
    private ?int $amountOverrideCents;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(string $id, string $householdId, string $billId, string $month, string $status, ?DateTimeImmutable $paidOn, ?int $amountOverrideCents)
    {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->billId = $billId;
        $this->month = $month;
        $this->createdAt = new DateTimeImmutable();
        $this->changeDetails($status, $paidOn, $amountOverrideCents);
    }

    public function changeDetails(string $status, ?DateTimeImmutable $paidOn, ?int $amountOverrideCents): void
    {
        if (!in_array($status, ['planned', 'paid', 'skipped'], true)) {
            $status = 'planned';
        }

        $this->status = $status;
        $this->paidOn = $status === 'paid' ? $paidOn : null;
        $this->amountOverrideCents = $amountOverrideCents;
    }

    public function id(): string { return $this->id; }
    public function billId(): string { return $this->billId; }
    public function householdId(): string { return $this->householdId; }
    public function month(): string { return $this->month; }
    public function status(): string { return $this->status; }
    public function paidOn(): ?DateTimeImmutable { return $this->paidOn; }
    public function amountOverrideCents(): ?int { return $this->amountOverrideCents; }
}
