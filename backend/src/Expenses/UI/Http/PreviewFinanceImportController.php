<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Import\FinanceImportService;
use App\Identity\Application\Security\HouseholdAccess;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PreviewFinanceImportController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private FinanceImportService $financeImport,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/import/preview', name: 'api_expenses_import_preview', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $file = $request->files->get('file');

        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return new JsonResponse(['error' => 'CSV file is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            return new JsonResponse($this->financeImport->preview(
                $householdId,
                $file,
                (string) $request->request->get('source', 'bank-csv'),
            ));
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
