<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Import\FinanceImportService;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AcceptFinanceImportController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private FinanceImportService $financeImport,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/import/accept', name: 'api_expenses_import_accept', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $file = $request->files->get('file');

        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return new JsonResponse(['error' => 'CSV file is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->financeImport->accept(
                $householdId,
                $file,
                (string) $request->request->get('source', 'bank-csv'),
            );
            $this->audit->record($householdId, $actor, 'finance_import', (string) ($result['batchId'] ?? sha1($file->getClientOriginalName().microtime())), 'import', 'Finance import accepted.', [
                'summary' => $result['summary'] ?? null,
                'source' => (string) $request->request->get('source', 'bank-csv'),
            ]);

            return new JsonResponse($result, JsonResponse::HTTP_CREATED);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
