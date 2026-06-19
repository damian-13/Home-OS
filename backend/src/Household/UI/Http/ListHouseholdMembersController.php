<?php

namespace App\Household\UI\Http;

use App\Household\Application\Query\ListHouseholdMembersQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListHouseholdMembersController
{
    public function __construct(
        private QueryBus $queryBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/members', name: 'api_household_members_list', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse([
            'items' => $this->queryBus->ask(new ListHouseholdMembersQuery($householdId)),
        ]);
    }
}
