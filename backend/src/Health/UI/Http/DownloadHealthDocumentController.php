<?php

namespace App\Health\UI\Http;

use App\Health\Domain\Repository\HealthRepository;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Infrastructure\File\SafeUploadedFileStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DownloadHealthDocumentController
{
    public function __construct(
        private HealthRepository $health,
        private HouseholdAccess $householdAccess,
        private string $healthDocumentsDir,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/health/documents/{documentId}/download', name: 'api_health_documents_download', methods: ['GET'])]
    public function __invoke(string $householdId, string $documentId): BinaryFileResponse|JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);

        $document = $this->health->documentById($householdId, $documentId);

        if (!$document) {
            return new JsonResponse(['error' => 'Document not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $path = SafeUploadedFileStorage::resolveStoredPath($this->healthDocumentsDir, $document->storedName());

        if ($path === null) {
            return new JsonResponse(['error' => 'Stored file not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $document->mimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $document->originalName());
        $this->audit->record($householdId, $actor, 'health_document', $documentId, 'download', 'Health document downloaded.', [
            'originalName' => $document->originalName(),
        ]);

        return $response;
    }
}
