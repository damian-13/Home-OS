<?php

namespace App\Health\UI\Http;

use App\Health\Application\Query\GetHealthOverviewQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetHealthOverviewController
{
    public function __construct(
        private QueryBus $queryBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/health/overview', name: 'api_health_overview', methods: ['GET'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new GetHealthOverviewQuery(
            $householdId,
            $request->query->get('memberId'),
        )));
    }
}
