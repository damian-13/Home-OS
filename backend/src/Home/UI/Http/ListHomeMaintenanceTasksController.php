<?php

namespace App\Home\UI\Http;

use App\Home\Application\Query\ListHomeMaintenanceTasksQuery;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListHomeMaintenanceTasksController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/households/{householdId}/home/maintenance-tasks', name: 'api_home_maintenance_tasks_list', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse([
            'tasks' => $this->queryBus->ask(new ListHomeMaintenanceTasksQuery($householdId)),
        ]);
    }
}
