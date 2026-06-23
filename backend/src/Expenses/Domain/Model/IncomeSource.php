<?php

namespace App\Expenses\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'income_sources')]
#[ORM\Index(name: 'IDX_INCOME_SOURCES_HOUSEHOLD_ACTIVE', columns: ['household_id', 'active'])]
class IncomeSource
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $memberId;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(string $id, string $householdId, ?string $memberId, string $name, int $amountCents, string $currency)
    {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->memberId = $memberId;
        $this->name = $name;
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->active = true;
        $this->createdAt = new DateTimeImmutable();
    }

    public function changeDetails(?string $memberId, string $name, int $amountCents, bool $active): void
    {
        $this->memberId = $memberId;
        $this->name = $name;
        $this->amountCents = $amountCents;
        $this->active = $active;
    }

    public function delete(): void
    {
        $this->deletedAt ??= new DateTimeImmutable();
        $this->active = false;
    }

    public function id(): string { return $this->id; }
    public function householdId(): string { return $this->householdId; }
    public function memberId(): ?string { return $this->memberId; }
    public function name(): string { return $this->name; }
    public function amountCents(): int { return $this->amountCents; }
    public function currency(): string { return $this->currency; }
    public function active(): bool { return $this->active; }
}
