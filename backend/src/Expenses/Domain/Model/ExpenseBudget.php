<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expense_budgets')]
#[ORM\UniqueConstraint(name: 'UNIQ_EXPENSE_BUDGET_MONTH_CATEGORY', columns: ['household_id', 'category_id', 'budget_month'])]
class ExpenseBudget
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\ManyToOne(targetEntity: ExpenseCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    private ExpenseCategory $category;

    #[ORM\Column(name: 'budget_month', type: 'string', length: 7)]
    private string $month;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(string $id, string $householdId, ExpenseCategory $category, string $month, int $amountCents)
    {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->category = $category;
        $this->month = $month;
        $this->amountCents = $amountCents;
        $this->createdAt = new DateTimeImmutable();
    }

    public function changeAmount(int $amountCents): void
    {
        $this->amountCents = $amountCents;
    }

    public function id(): string { return $this->id; }
    public function category(): ExpenseCategory { return $this->category; }
    public function month(): string { return $this->month; }
    public function amountCents(): int { return $this->amountCents; }
}
