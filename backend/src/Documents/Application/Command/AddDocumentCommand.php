<?php

namespace App\Documents\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class AddDocumentCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $title,
        public string $type,
        public ?string $ownerMemberId,
        public ?string $issuedAt,
        public ?string $expiresAt,
        public ?string $tags,
        public ?string $note,
        public ?string $originalName,
        public ?string $storedName,
        public ?string $mimeType,
        public ?int $fileSize,
    ) {
    }
}
