<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'finance_review_rules')]
#[ORM\Index(name: 'IDX_FINANCE_REVIEW_RULES_HOUSEHOLD_TARGET', columns: ['household_id', 'target_type'])]
class FinanceReviewRule
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 16)]
    private string $targetType;

    #[ORM\Column(type: 'string', length: 80)]
    private string $matchText;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $categoryId;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $incomeKind;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastAppliedAt = null;

    public function __construct(string $id, string $householdId, string $targetType, string $matchText, ?string $categoryId, ?string $incomeKind)
    {
        if (!in_array($targetType, ['expense', 'income'], true)) {
            throw new \InvalidArgumentException('Unsupported finance review rule target type.');
        }

        if ($targetType === 'expense' && (!$categoryId || $incomeKind)) {
            throw new \InvalidArgumentException('Expense rules require category only.');
        }

        if ($targetType === 'income' && (!$incomeKind || $categoryId)) {
            throw new \InvalidArgumentException('Income rules require income kind only.');
        }

        if ($incomeKind && !in_array($incomeKind, ['salary', 'transfer', 'refund', 'other'], true)) {
            throw new \InvalidArgumentException('Unsupported income kind.');
        }

        $this->id = $id;
        $this->householdId = $householdId;
        $this->targetType = $targetType;
        $this->matchText = $matchText;
        $this->categoryId = $categoryId;
        $this->incomeKind = $incomeKind;
        $this->createdAt = new DateTimeImmutable();
    }

    public function markApplied(): void
    {
        $this->lastAppliedAt = new DateTimeImmutable();
    }

    public function id(): string { return $this->id; }
    public function householdId(): string { return $this->householdId; }
    public function targetType(): string { return $this->targetType; }
    public function matchText(): string { return $this->matchText; }
    public function categoryId(): ?string { return $this->categoryId; }
    public function incomeKind(): ?string { return $this->incomeKind; }
    public function active(): bool { return $this->active; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function lastAppliedAt(): ?DateTimeImmutable { return $this->lastAppliedAt; }
}
