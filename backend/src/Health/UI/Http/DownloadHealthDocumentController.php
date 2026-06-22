<?php

namespace App\Health\UI\Http;

use App\Health\Domain\Repository\HealthRepository;
use App\Identity\Application\Security\HouseholdAccess;
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
    ) {
    }

    #[Route('/api/households/{householdId}/health/documents/{documentId}/download', name: 'api_health_documents_download', methods: ['GET'])]
    public function __invoke(string $householdId, string $documentId): BinaryFileResponse|JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        $document = $this->health->documentById($householdId, $documentId);

        if (!$document) {
            return new JsonResponse(['error' => 'Document not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $path = sprintf('%s/%s', rtrim($this->healthDocumentsDir, '/'), $document->storedName());

        if (!is_file($path)) {
            return new JsonResponse(['error' => 'Stored file not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $document->mimeType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $document->originalName());

        return $response;
    }
}
