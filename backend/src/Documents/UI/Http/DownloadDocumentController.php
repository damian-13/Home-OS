<?php

namespace App\Documents\UI\Http;

use App\Documents\Domain\Repository\DocumentRepository;
use App\Identity\Application\Security\HouseholdAccess;
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
    ) {
    }

    #[Route('/api/households/{householdId}/documents/{documentId}/download', name: 'api_documents_download', methods: ['GET'])]
    public function __invoke(string $householdId, string $documentId): BinaryFileResponse|JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $document = $this->documents->get($householdId, $documentId);

        if (!$document->storedName() || !$document->originalName() || !$document->mimeType()) {
            return new JsonResponse(['error' => 'Document has no file.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $path = sprintf('%s/%s', rtrim($this->documentsDir, '/'), $document->storedName());

        if (!is_file($path)) {
            return new JsonResponse(['error' => 'Stored file not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $document->mimeType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $document->originalName());

        return $response;
    }
}
