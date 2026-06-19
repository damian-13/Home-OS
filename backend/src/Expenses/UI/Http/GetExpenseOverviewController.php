<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Query\GetExpenseOverviewQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetExpenseOverviewController
{
    public function __construct(
        private QueryBus $queryBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/overview', name: 'api_expenses_overview', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new GetExpenseOverviewQuery($householdId)));
    }
}
