<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'recurring_bills')]
#[ORM\Index(name: 'IDX_RECURRING_BILLS_HOUSEHOLD_DUE_DAY', columns: ['household_id', 'due_day'])]
class RecurringBill
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\ManyToOne(targetEntity: ExpenseCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    private ExpenseCategory $category;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'smallint')]
    private int $dueDay;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $paidByMemberId;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $householdId,
        ExpenseCategory $category,
        string $name,
        int $amountCents,
        string $currency,
        int $dueDay,
        ?string $paidByMemberId,
    ) {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->category = $category;
        $this->name = $name;
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->dueDay = $dueDay;
        $this->paidByMemberId = $paidByMemberId;
        $this->active = true;
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function householdId(): string
    {
        return $this->householdId;
    }

    public function category(): ExpenseCategory
    {
        return $this->category;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function amountCents(): int
    {
        return $this->amountCents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function dueDay(): int
    {
        return $this->dueDay;
    }

    public function paidByMemberId(): ?string
    {
        return $this->paidByMemberId;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
