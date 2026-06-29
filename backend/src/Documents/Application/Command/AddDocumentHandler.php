<?php

namespace App\Documents\Application\Command;

use App\Documents\Domain\Model\Document;
use App\Documents\Domain\Repository\DocumentRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;

final readonly class AddDocumentHandler implements CommandHandler
{
    public function __construct(
        private DocumentRepository $documents,
    ) {
    }

    public function handles(): string
    {
        return AddDocumentCommand::class;
    }

    public function __invoke(AddDocumentCommand $command): string
    {
        $document = new Document(
            (string) Uuid::new(),
            $command->householdId,
            $command->title,
            $command->type,
            $command->ownerMemberId,
            $command->issuedAt ? new DateTimeImmutable($command->issuedAt) : null,
            $command->expiresAt ? new DateTimeImmutable($command->expiresAt) : null,
            $command->tags,
            $command->note,
            $command->originalName,
            $command->storedName,
            $command->mimeType,
            $command->fileSize,
        );

        $this->documents->save($document);

        return $document->id();
    }
}
