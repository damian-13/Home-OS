<?php

namespace App\Health\Application\Command;

use App\Health\Domain\Model\HealthDocument;
use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AddHealthDocumentHandler implements CommandHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return AddHealthDocumentCommand::class;
    }

    public function __invoke(AddHealthDocumentCommand $command): string
    {
        if ($command->originalName === '' || $command->storedName === '') {
            throw new InvalidArgumentException('Document file is required.');
        }

        $document = new HealthDocument(
            (string) Uuid::new(),
            $command->householdId,
            $command->memberId,
            $command->documentType,
            $command->originalName,
            $command->storedName,
            $command->mimeType,
            $command->size,
            new DateTimeImmutable(),
        );

        $this->health->saveDocument($document);

        return $document->id();
    }
}
