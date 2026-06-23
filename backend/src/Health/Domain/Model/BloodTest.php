<?php

namespace App\Health\Domain\Model;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'blood_tests')]
#[ORM\Index(name: 'IDX_BLOOD_TESTS_HOUSEHOLD_TESTED_AT', columns: ['household_id', 'tested_at'])]
class BloodTest
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $memberId;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $testedAt;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $labName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(name: 'source_document_id', type: 'string', length: 36, nullable: true)]
    private ?string $sourceDocumentId;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    /**
     * @var Collection<int, BloodTestMarker>
     */
    #[ORM\OneToMany(mappedBy: 'bloodTest', targetEntity: BloodTestMarker::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $markers;

    public function __construct(
        string $id,
        string $householdId,
        string $memberId,
        DateTimeImmutable $testedAt,
        ?string $labName,
        ?string $notes,
        ?string $sourceDocumentId = null,
    )
    {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->memberId = $memberId;
        $this->testedAt = $testedAt;
        $this->labName = $labName;
        $this->notes = $notes;
        $this->sourceDocumentId = $sourceDocumentId;
        $this->createdAt = new DateTimeImmutable();
        $this->markers = new ArrayCollection();
    }

    public function addMarker(
        string $id,
        string $name,
        float $value,
        string $unit,
        ?float $referenceMin,
        ?float $referenceMax,
        string $status,
        ?string $notes,
    ): BloodTestMarker {
        $marker = new BloodTestMarker($id, $this, $name, $value, $unit, $referenceMin, $referenceMax, $status, $notes);
        $this->markers->add($marker);

        return $marker;
    }

    /**
     * @param list<array{id: string, name: string, value: float, unit: string, referenceMin: float|null, referenceMax: float|null, status: string, notes: string|null}> $markers
     */
    public function replaceDetails(
        string $memberId,
        DateTimeImmutable $testedAt,
        ?string $labName,
        ?string $notes,
        array $markers,
    ): void {
        $this->memberId = $memberId;
        $this->testedAt = $testedAt;
        $this->labName = $labName;
        $this->notes = $notes;
        $this->markers->clear();

        foreach ($markers as $marker) {
            $this->addMarker(
                $marker['id'],
                $marker['name'],
                $marker['value'],
                $marker['unit'],
                $marker['referenceMin'],
                $marker['referenceMax'],
                $marker['status'],
                $marker['notes'],
            );
        }
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function householdId(): string
    {
        return $this->householdId;
    }

    public function memberId(): string
    {
        return $this->memberId;
    }

    public function testedAt(): DateTimeImmutable
    {
        return $this->testedAt;
    }

    public function labName(): ?string
    {
        return $this->labName;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function sourceDocumentId(): ?string
    {
        return $this->sourceDocumentId;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /**
     * @return list<BloodTestMarker>
     */
    public function markers(): array
    {
        return $this->markers->toArray();
    }
}
