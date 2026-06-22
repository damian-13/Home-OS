<?php

namespace App\Health\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class AddHealthDocumentCommand implements Command
{
    public function __construct(
        public string $householdId,
        public ?string $memberId,
        public string $documentType,
        public string $originalName,
        public string $storedName,
        public string $mimeType,
        public int $size,
    ) {
    }
}
