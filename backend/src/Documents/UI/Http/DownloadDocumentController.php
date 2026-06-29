<?php

namespace App\Documents\UI\Http;

use App\Documents\Domain\Repository\DocumentRepository;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Infrastructure\File\SafeUploadedFileStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DownloadDocumentController
{
    public function __construct(
        private DocumentRepository $documents,
        private HouseholdAccess $householdAccess,
        private string $documentsDir,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/documents/{documentId}/download', name: 'api_documents_download', methods: ['GET'])]
    public function __invoke(string $householdId, string $documentId): BinaryFileResponse|JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $document = $this->documents->get($householdId, $documentId);

        if (!$document->storedName() || !$document->originalName() || !$document->mimeType()) {
            return new JsonResponse(['error' => 'Document has no file.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $path = SafeUploadedFileStorage::resolveStoredPath($this->documentsDir, $document->storedName());

        if ($path === null) {
            return new JsonResponse(['error' => 'Stored file not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $document->mimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $document->originalName());
        $this->audit->record($householdId, $actor, 'document', $documentId, 'download', 'Document downloaded.', [
            'originalName' => $document->originalName(),
        ]);

        return $response;
    }
}
