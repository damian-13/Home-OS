<?php

namespace App\Home\UI\Http;

use App\Home\Application\Command\DeleteHomeMaintenanceTaskCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteHomeMaintenanceTaskController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/home/maintenance-tasks/{taskId}', name: 'api_home_maintenance_tasks_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $taskId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new DeleteHomeMaintenanceTaskCommand($householdId, $taskId));

        return new JsonResponse(null, 204);
    }
}
