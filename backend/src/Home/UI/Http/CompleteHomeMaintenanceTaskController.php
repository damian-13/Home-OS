<?php

namespace App\Home\UI\Http;

use App\Home\Application\Command\CompleteHomeMaintenanceTaskCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CompleteHomeMaintenanceTaskController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/home/maintenance-tasks/{taskId}/complete', name: 'api_home_maintenance_tasks_complete', methods: ['POST'])]
    public function __invoke(string $householdId, string $taskId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new CompleteHomeMaintenanceTaskCommand($householdId, $taskId));

        return new JsonResponse(['status' => 'completed']);
    }
}
