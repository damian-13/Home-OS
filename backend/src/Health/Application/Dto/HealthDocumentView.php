<?php

namespace App\Health\Application\Dto;

use App\Health\Domain\Model\HealthDocument;

final readonly class HealthDocumentView
{
    public function __construct(
        public string $id,
        public ?string $memberId,
        public string $documentType,
        public string $originalName,
        public string $mimeType,
        public int $size,
        public string $uploadedAt,
        public string $downloadUrl,
    ) {
    }

    public static function fromDocument(HealthDocument $document): self
    {
        return new self(
            $document->id(),
            $document->memberId(),
            $document->documentType(),
            $document->originalName(),
            $document->mimeType(),
            $document->size(),
            $document->uploadedAt()->format(DATE_ATOM),
            sprintf('/api/households/%s/health/documents/%s/download', $document->householdId(), $document->id()),
        );
    }
}
