<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'income_entries')]
#[ORM\Index(name: 'IDX_INCOME_ENTRIES_HOUSEHOLD_RECEIVED_ON', columns: ['household_id', 'received_on'])]
class IncomeEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $sourceId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $memberId;

    #[ORM\Column(type: 'string', length: 120)]
    private string $description;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $receivedOn;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(string $id, string $householdId, ?string $sourceId, ?string $memberId, string $description, int $amountCents, string $currency, DateTimeImmutable $receivedOn)
    {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->sourceId = $sourceId;
        $this->memberId = $memberId;
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->receivedOn = $receivedOn;
        $this->createdAt = new DateTimeImmutable();
    }

    public function changeDetails(?string $sourceId, ?string $memberId, string $description, int $amountCents, DateTimeImmutable $receivedOn): void
    {
        $this->sourceId = $sourceId;
        $this->memberId = $memberId;
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->receivedOn = $receivedOn;
    }

    public function delete(): void
    {
        $this->deletedAt ??= new DateTimeImmutable();
    }

    public function id(): string { return $this->id; }
    public function householdId(): string { return $this->householdId; }
    public function sourceId(): ?string { return $this->sourceId; }
    public function memberId(): ?string { return $this->memberId; }
    public function description(): string { return $this->description; }
    public function amountCents(): int { return $this->amountCents; }
    public function currency(): string { return $this->currency; }
    public function receivedOn(): DateTimeImmutable { return $this->receivedOn; }
}
