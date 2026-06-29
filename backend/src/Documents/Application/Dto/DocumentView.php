<?php

namespace App\Documents\Application\Dto;

use App\Documents\Domain\Model\Document;

final readonly class DocumentView
{
    public function __construct(
        public string $id,
        public string $householdId,
        public string $title,
        public string $type,
        public ?string $ownerMemberId,
        public ?string $issuedAt,
        public ?string $expiresAt,
        public ?string $tags,
        public ?string $note,
        public ?string $originalName,
        public ?string $mimeType,
        public ?int $fileSize,
        public ?string $downloadUrl,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromDocument(Document $document): self
    {
        return new self(
            $document->id(),
            $document->householdId(),
            $document->title(),
            $document->type(),
            $document->ownerMemberId(),
            $document->issuedAt()?->format('Y-m-d'),
            $document->expiresAt()?->format('Y-m-d'),
            $document->tags(),
            $document->note(),
            $document->originalName(),
            $document->mimeType(),
            $document->fileSize(),
            $document->storedName() ? sprintf('/api/households/%s/documents/%s/download', $document->householdId(), $document->id()) : null,
            $document->createdAt()->format(DATE_ATOM),
            $document->updatedAt()?->format(DATE_ATOM),
        );
    }
}
