<?php

namespace App\Health\UI\Http;

use App\Health\Application\Query\GetHealthReviewQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetHealthReviewController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/households/{householdId}/health/review', name: 'api_health_review', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new GetHealthReviewQuery($householdId)));
    }
}
