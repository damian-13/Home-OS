<?php

namespace App\Documents\UI\Http;

use App\Documents\Application\Command\UpdateDocumentCommand;
use App\Documents\Domain\Model\Document;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateDocumentController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/documents/{documentId}', name: 'api_documents_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $documentId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new UpdateDocumentCommand(
            $householdId,
            $documentId,
            (string) ($payload['title'] ?? ''),
            (string) ($payload['type'] ?? Document::TYPE_OTHER),
            isset($payload['ownerMemberId']) && trim((string) $payload['ownerMemberId']) !== '' ? (string) $payload['ownerMemberId'] : null,
            isset($payload['issuedAt']) && trim((string) $payload['issuedAt']) !== '' ? (string) $payload['issuedAt'] : null,
            isset($payload['expiresAt']) && trim((string) $payload['expiresAt']) !== '' ? (string) $payload['expiresAt'] : null,
            isset($payload['tags']) && trim((string) $payload['tags']) !== '' ? (string) $payload['tags'] : null,
            isset($payload['note']) && trim((string) $payload['note']) !== '' ? (string) $payload['note'] : null,
        ));

        return new JsonResponse(['status' => 'updated']);
    }
}
