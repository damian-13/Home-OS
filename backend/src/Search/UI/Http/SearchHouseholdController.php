<?php

namespace App\Search\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Search\Application\Query\SearchHouseholdQuery;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SearchHouseholdController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/households/{householdId}/search', name: 'api_household_search', methods: ['GET'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new SearchHouseholdQuery(
            $householdId,
            (string) $request->query->get('q', ''),
        )));
    }
}
