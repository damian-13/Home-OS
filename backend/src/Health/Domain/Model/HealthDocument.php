<?php

namespace App\Health\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'health_documents')]
#[ORM\Index(name: 'IDX_HEALTH_DOCUMENTS_HOUSEHOLD_UPLOADED_AT', columns: ['household_id', 'uploaded_at'])]
final class HealthDocument
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(name: 'household_id', type: 'guid')]
        private string $householdId,
        #[ORM\Column(name: 'member_id', type: 'guid', nullable: true)]
        private ?string $memberId,
        #[ORM\Column(name: 'document_type', length: 40)]
        private string $documentType,
        #[ORM\Column(name: 'original_name', length: 255)]
        private string $originalName,
        #[ORM\Column(name: 'stored_name', length: 255)]
        private string $storedName,
        #[ORM\Column(name: 'mime_type', length: 120)]
        private string $mimeType,
        #[ORM\Column(type: 'integer')]
        private int $size,
        #[ORM\Column(name: 'uploaded_at')]
        private DateTimeImmutable $uploadedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function householdId(): string
    {
        return $this->householdId;
    }

    public function memberId(): ?string
    {
        return $this->memberId;
    }

    public function documentType(): string
    {
        return $this->documentType;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function storedName(): string
    {
        return $this->storedName;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function uploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }
}
