<?php

namespace App\Health\UI\Http;

use App\Health\Application\Command\AddHealthDocumentCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use App\Shared\Infrastructure\File\SafeUploadedFileStorage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UploadHealthDocumentController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
        private string $healthDocumentsDir,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/health/documents', name: 'api_health_documents_upload', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return new JsonResponse(['error' => 'Valid document file is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $stored = SafeUploadedFileStorage::store($file, $householdId, $this->healthDocumentsDir);
            $id = $this->commandBus->dispatch(new AddHealthDocumentCommand(
                $householdId,
                trim((string) $request->request->get('memberId', '')) !== '' ? trim((string) $request->request->get('memberId')) : null,
                trim((string) $request->request->get('documentType', 'lab_result')) ?: 'lab_result',
                $stored['originalName'],
                $stored['storedName'],
                $stored['mimeType'],
                $stored['size'],
            ));
            $this->audit->record($householdId, $actor, 'health_document', $id, 'create', 'Health document uploaded.', [
                'originalName' => $stored['originalName'],
                'mimeType' => $stored['mimeType'],
            ]);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['id' => $id], JsonResponse::HTTP_CREATED);
    }
}
