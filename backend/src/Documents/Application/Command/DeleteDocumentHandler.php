<?php

namespace App\Documents\Application\Command;

use App\Documents\Domain\Repository\DocumentRepository;
use App\Shared\Application\Command\CommandHandler;

final readonly class DeleteDocumentHandler implements CommandHandler
{
    public function __construct(
        private DocumentRepository $documents,
    ) {
    }

    public function handles(): string
    {
        return DeleteDocumentCommand::class;
    }

    public function __invoke(DeleteDocumentCommand $command): void
    {
        $document = $this->documents->get($command->householdId, $command->documentId);
        $document->delete();

        $this->documents->save($document);
    }
}
