<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'finance_review_batches')]
#[ORM\Index(name: 'IDX_FINANCE_REVIEW_BATCHES_HOUSEHOLD_CREATED', columns: ['household_id', 'created_at'])]
class FinanceReviewBatch
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $ruleId;

    #[ORM\Column(type: 'string', length: 16)]
    private string $targetType;

    #[ORM\Column(type: 'string', length: 80)]
    private string $matchText;

    #[ORM\Column(type: 'integer')]
    private int $appliedCount;

    /** @var list<array<string, mixed>> */
    #[ORM\Column(type: 'json')]
    private array $items;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $undoneAt = null;

    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(string $id, string $householdId, string $ruleId, string $targetType, string $matchText, array $items)
    {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->ruleId = $ruleId;
        $this->targetType = $targetType;
        $this->matchText = $matchText;
        $this->appliedCount = count($items);
        $this->items = $items;
        $this->createdAt = new DateTimeImmutable();
    }

    public function markUndone(): void
    {
        $this->undoneAt ??= new DateTimeImmutable();
    }

    public function id(): string { return $this->id; }
    public function householdId(): string { return $this->householdId; }
    public function ruleId(): string { return $this->ruleId; }
    public function targetType(): string { return $this->targetType; }
    public function matchText(): string { return $this->matchText; }
    public function appliedCount(): int { return $this->appliedCount; }
    /** @return list<array<string, mixed>> */
    public function items(): array { return $this->items; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function undoneAt(): ?DateTimeImmutable { return $this->undoneAt; }
}
