<?php

namespace App\Documents\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class UpdateDocumentCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $documentId,
        public string $title,
        public string $type,
        public ?string $ownerMemberId,
        public ?string $issuedAt,
        public ?string $expiresAt,
        public ?string $tags,
        public ?string $note,
    ) {
    }
}
