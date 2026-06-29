<?php

namespace App\Documents\UI\Http;

use App\Documents\Application\Query\ListDocumentsQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListDocumentsController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/households/{householdId}/documents', name: 'api_documents_list', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse([
            'documents' => $this->queryBus->ask(new ListDocumentsQuery($householdId)),
        ]);
    }
}
