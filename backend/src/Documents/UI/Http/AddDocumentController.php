<?php

namespace App\Documents\UI\Http;

use App\Documents\Application\Command\AddDocumentCommand;
use App\Documents\Domain\Model\Document;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddDocumentController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
        private string $documentsDir,
    ) {
    }

    #[Route('/api/households/{householdId}/documents', name: 'api_documents_add', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $file = $request->files->get('file');
        $stored = ['originalName' => null, 'storedName' => null, 'mimeType' => null, 'size' => null];

        try {
            if ($file instanceof UploadedFile) {
                $stored = DocumentFileStorage::store($file, $householdId, $this->documentsDir);
            }

            $id = $this->commandBus->dispatch(new AddDocumentCommand(
                $householdId,
                (string) $request->request->get('title', ''),
                (string) $request->request->get('type', Document::TYPE_OTHER),
                trim((string) $request->request->get('ownerMemberId', '')) !== '' ? trim((string) $request->request->get('ownerMemberId')) : null,
                trim((string) $request->request->get('issuedAt', '')) !== '' ? trim((string) $request->request->get('issuedAt')) : null,
                trim((string) $request->request->get('expiresAt', '')) !== '' ? trim((string) $request->request->get('expiresAt')) : null,
                trim((string) $request->request->get('tags', '')) !== '' ? trim((string) $request->request->get('tags')) : null,
                trim((string) $request->request->get('note', '')) !== '' ? trim((string) $request->request->get('note')) : null,
                $stored['originalName'],
                $stored['storedName'],
                $stored['mimeType'],
                $stored['size'],
            ));
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['id' => $id], JsonResponse::HTTP_CREATED);
    }
}
