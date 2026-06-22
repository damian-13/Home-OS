<?php

namespace App\Health\UI\Http;

use App\Health\Application\Query\ListHealthDocumentsQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListHealthDocumentsController
{
    public function __construct(
        private QueryBus $queryBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/health/documents', name: 'api_health_documents_list', methods: ['GET'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new ListHealthDocumentsQuery(
            $householdId,
            $request->query->get('memberId') ? (string) $request->query->get('memberId') : null,
        )));
    }
}
