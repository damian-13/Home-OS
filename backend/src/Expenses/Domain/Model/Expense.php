<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expenses')]
#[ORM\Index(name: 'IDX_EXPENSES_HOUSEHOLD_SPENT_ON', columns: ['household_id', 'spent_on'])]
class Expense
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
    private string $description;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $spentOn;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $paidByMemberId;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: 'string', length: 16)]
    private string $reviewStatus = 'reviewed';

    #[ORM\Column(type: 'string', length: 160, nullable: true)]
    private ?string $reviewReason = null;

    public function __construct(
        string $id,
        string $householdId,
        ExpenseCategory $category,
        string $description,
        int $amountCents,
        string $currency,
        DateTimeImmutable $spentOn,
        ?string $paidByMemberId,
    ) {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->category = $category;
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->spentOn = $spentOn;
        $this->paidByMemberId = $paidByMemberId;
        $this->createdAt = new DateTimeImmutable();
    }

    public function changeDetails(ExpenseCategory $category, string $description, int $amountCents, DateTimeImmutable $spentOn, ?string $paidByMemberId): void
    {
        $this->category = $category;
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->spentOn = $spentOn;
        $this->paidByMemberId = $paidByMemberId;
    }

    public function changeReview(string $status, ?string $reason = null): void
    {
        if (!in_array($status, ['needs_review', 'reviewed'], true)) {
            throw new \InvalidArgumentException('Unsupported expense review status.');
        }

        $this->reviewStatus = $status;
        $this->reviewReason = $status === 'needs_review' ? $reason : null;
    }

    public function delete(): void
    {
        $this->deletedAt ??= new DateTimeImmutable();
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

    public function description(): string
    {
        return $this->description;
    }

    public function amountCents(): int
    {
        return $this->amountCents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function spentOn(): DateTimeImmutable
    {
        return $this->spentOn;
    }

    public function paidByMemberId(): ?string
    {
        return $this->paidByMemberId;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function reviewStatus(): string
    {
        return $this->reviewStatus;
    }

    public function reviewReason(): ?string
    {
        return $this->reviewReason;
    }
}
