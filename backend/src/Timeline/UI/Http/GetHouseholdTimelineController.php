<?php

namespace App\Timeline\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use App\Timeline\Application\Query\GetHouseholdTimelineQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetHouseholdTimelineController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/households/{householdId}/timeline', name: 'api_household_timeline', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new GetHouseholdTimelineQuery($householdId)));
    }
}
