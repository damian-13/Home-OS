<?php

namespace App\Health\UI\Http;

use App\Health\Domain\Repository\HealthRepository;
use App\Health\Infrastructure\Document\DocumentTextExtractor;
use App\Health\Infrastructure\Document\LabResultTextParser;
use App\Identity\Application\Security\HouseholdAccess;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ExtractHealthDocumentTextController
{
    public function __construct(
        private HealthRepository $health,
        private HouseholdAccess $householdAccess,
        private DocumentTextExtractor $extractor,
        private LabResultTextParser $parser,
    ) {
    }

    #[Route('/api/households/{householdId}/health/documents/{documentId}/extract-text', name: 'api_health_documents_extract_text', methods: ['POST'])]
    public function __invoke(string $householdId, string $documentId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        $document = $this->health->documentById($householdId, $documentId);

        if (!$document) {
            return new JsonResponse(['error' => 'Document not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $result = $this->extractor->extract($document);

        return new JsonResponse([
            'documentId' => $document->id(),
            'status' => $result['status'],
            'text' => $result['text'],
            'message' => $result['message'],
            'suggestedTestedAt' => $result['text'] !== '' ? $this->parser->testedAt($result['text']) : null,
            'markers' => $result['text'] !== '' ? $this->parser->parse($result['text']) : [],
        ]);
    }
}
