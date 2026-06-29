<?php

namespace App\Documents\Application\Command;

use App\Documents\Domain\Repository\DocumentRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;

final readonly class UpdateDocumentHandler implements CommandHandler
{
    public function __construct(
        private DocumentRepository $documents,
    ) {
    }

    public function handles(): string
    {
        return UpdateDocumentCommand::class;
    }

    public function __invoke(UpdateDocumentCommand $command): void
    {
        $document = $this->documents->get($command->householdId, $command->documentId);
        $document->changeDetails(
            $command->title,
            $command->type,
            $command->ownerMemberId,
            $command->issuedAt ? new DateTimeImmutable($command->issuedAt) : null,
            $command->expiresAt ? new DateTimeImmutable($command->expiresAt) : null,
            $command->tags,
            $command->note,
        );

        $this->documents->save($document);
    }
}
