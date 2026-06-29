<?php

namespace App\Documents\UI\Http;

use App\Documents\Application\Command\DeleteDocumentCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteDocumentController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/documents/{documentId}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $documentId): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new DeleteDocumentCommand($householdId, $documentId));
        $this->audit->record($householdId, $actor, 'document', $documentId, 'delete', 'Document deleted.');

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
