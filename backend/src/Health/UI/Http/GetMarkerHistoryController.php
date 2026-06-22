<?php

namespace App\Health\UI\Http;

use App\Health\Application\Query\GetMarkerHistoryQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetMarkerHistoryController
{
    public function __construct(
        private QueryBus $queryBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/health/markers/{markerName}/history', name: 'api_health_marker_history', methods: ['GET'])]
    public function __invoke(string $householdId, string $markerName, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new GetMarkerHistoryQuery(
            $householdId,
            urldecode($markerName),
            $request->query->get('memberId'),
        )));
    }
}
