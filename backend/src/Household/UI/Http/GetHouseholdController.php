<?php

namespace App\Household\UI\Http;

use App\Household\Application\Query\GetHouseholdQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetHouseholdController
{
    public function __construct(
        private QueryBus $queryBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}', name: 'api_households_get', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new GetHouseholdQuery($householdId)));
    }
}
