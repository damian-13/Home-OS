<?php

namespace App\Documents\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class DeleteDocumentCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $documentId,
    ) {
    }
}
