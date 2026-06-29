<?php

namespace App\Documents\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'documents')]
#[ORM\Index(name: 'IDX_DOCUMENTS_HOUSEHOLD_TYPE', columns: ['household_id', 'document_type'])]
#[ORM\Index(name: 'IDX_DOCUMENTS_HOUSEHOLD_EXPIRES', columns: ['household_id', 'expires_at'])]
#[ORM\Index(name: 'IDX_DOCUMENTS_HOUSEHOLD_DELETED', columns: ['household_id', 'deleted_at'])]
class Document
{
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_MEDICAL = 'medical';
    public const TYPE_WARRANTY = 'warranty';
    public const TYPE_INSURANCE = 'insurance';
    public const TYPE_TAX = 'tax';
    public const TYPE_MANUAL = 'manual';
    public const TYPE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 180)]
    private string $title;

    #[ORM\Column(name: 'document_type', type: 'string', length: 24)]
    private string $type;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $ownerMemberId;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $issuedAt;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $tags;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $originalName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $storedName;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $mimeType;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fileSize;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $id,
        string $householdId,
        string $title,
        string $type,
        ?string $ownerMemberId,
        ?DateTimeImmutable $issuedAt,
        ?DateTimeImmutable $expiresAt,
        ?string $tags,
        ?string $note,
        ?string $originalName,
        ?string $storedName,
        ?string $mimeType,
        ?int $fileSize,
    ) {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->createdAt = new DateTimeImmutable();
        $this->changeDetails($title, $type, $ownerMemberId, $issuedAt, $expiresAt, $tags, $note);
        $this->replaceFile($originalName, $storedName, $mimeType, $fileSize);
    }

    public function changeDetails(
        string $title,
        string $type,
        ?string $ownerMemberId,
        ?DateTimeImmutable $issuedAt,
        ?DateTimeImmutable $expiresAt,
        ?string $tags,
        ?string $note,
    ): void {
        $title = trim($title);
        $ownerMemberId = $ownerMemberId !== null && trim($ownerMemberId) !== '' ? trim($ownerMemberId) : null;
        $tags = $tags !== null && trim($tags) !== '' ? trim($tags) : null;
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        if ($title === '') {
            throw new InvalidArgumentException('Document title is required.');
        }

        if (!in_array($type, self::types(), true)) {
            throw new InvalidArgumentException('Unsupported document type.');
        }

        $this->title = $title;
        $this->type = $type;
        $this->ownerMemberId = $ownerMemberId;
        $this->issuedAt = $issuedAt;
        $this->expiresAt = $expiresAt;
        $this->tags = $tags;
        $this->note = $note;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function replaceFile(?string $originalName, ?string $storedName, ?string $mimeType, ?int $fileSize): void
    {
        $this->originalName = $originalName !== null && trim($originalName) !== '' ? trim($originalName) : null;
        $this->storedName = $storedName !== null && trim($storedName) !== '' ? trim($storedName) : null;
        $this->mimeType = $mimeType !== null && trim($mimeType) !== '' ? trim($mimeType) : null;
        $this->fileSize = $fileSize;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function delete(): void
    {
        $this->deletedAt ??= new DateTimeImmutable();
    }

    public function id(): string { return $this->id; }
    public function householdId(): string { return $this->householdId; }
    public function title(): string { return $this->title; }
    public function type(): string { return $this->type; }
    public function ownerMemberId(): ?string { return $this->ownerMemberId; }
    public function issuedAt(): ?DateTimeImmutable { return $this->issuedAt; }
    public function expiresAt(): ?DateTimeImmutable { return $this->expiresAt; }
    public function tags(): ?string { return $this->tags; }
    public function note(): ?string { return $this->note; }
    public function originalName(): ?string { return $this->originalName; }
    public function storedName(): ?string { return $this->storedName; }
    public function mimeType(): ?string { return $this->mimeType; }
    public function fileSize(): ?int { return $this->fileSize; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
    public function deletedAt(): ?DateTimeImmutable { return $this->deletedAt; }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_CONTRACT,
            self::TYPE_INVOICE,
            self::TYPE_MEDICAL,
            self::TYPE_WARRANTY,
            self::TYPE_INSURANCE,
            self::TYPE_TAX,
            self::TYPE_MANUAL,
            self::TYPE_OTHER,
        ];
    }
}
