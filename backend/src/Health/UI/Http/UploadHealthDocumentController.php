<?php

namespace App\Health\UI\Http;

use App\Health\Application\Command\AddHealthDocumentCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use App\Shared\Domain\Uuid;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UploadHealthDocumentController
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const EXTENSIONS_BY_MIME_TYPE = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
        private string $healthDocumentsDir,
    ) {
    }

    #[Route('/api/households/{householdId}/health/documents', name: 'api_health_documents_upload', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return new JsonResponse(['error' => 'Valid document file is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $mimeType = $file->getClientMimeType();

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return new JsonResponse(['error' => 'Only PDF, JPG, PNG, and WebP files are supported.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() !== null && $file->getSize() > 10 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Document can be up to 10 MB.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $size = (int) $file->getSize();
        $extension = self::EXTENSIONS_BY_MIME_TYPE[$mimeType];
        $storedName = sprintf('%s/%s.%s', $householdId, Uuid::new(), strtolower($extension));
        $targetDirectory = sprintf('%s/%s', rtrim($this->healthDocumentsDir, '/'), $householdId);

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new InvalidArgumentException('Could not create health document directory.');
        }

        $file->move($targetDirectory, basename($storedName));

        try {
            $id = $this->commandBus->dispatch(new AddHealthDocumentCommand(
                $householdId,
                trim((string) $request->request->get('memberId', '')) !== '' ? trim((string) $request->request->get('memberId')) : null,
                trim((string) $request->request->get('documentType', 'lab_result')) ?: 'lab_result',
                $file->getClientOriginalName(),
                $storedName,
                $mimeType,
                $size,
            ));
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['id' => $id], JsonResponse::HTTP_CREATED);
    }
}
