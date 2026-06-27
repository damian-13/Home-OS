<?php

namespace App\Home\UI\Http;

use App\Home\Application\Command\UpdateHomeMaintenanceTaskCommand;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateHomeMaintenanceTaskController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/home/maintenance-tasks/{taskId}', name: 'api_home_maintenance_tasks_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $taskId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new UpdateHomeMaintenanceTaskCommand(
            $householdId,
            $taskId,
            (string) ($payload['title'] ?? ''),
            (string) ($payload['area'] ?? ''),
            (string) ($payload['nextDueAt'] ?? date('Y-m-d')),
            (string) ($payload['recurrenceType'] ?? HomeMaintenanceTask::RECURRENCE_NONE),
            isset($payload['assignedMemberId']) && trim((string) $payload['assignedMemberId']) !== '' ? (string) $payload['assignedMemberId'] : null,
            (string) ($payload['priority'] ?? HomeMaintenanceTask::PRIORITY_NORMAL),
            isset($payload['notes']) && trim((string) $payload['notes']) !== '' ? (string) $payload['notes'] : null,
        ));

        return new JsonResponse(['status' => 'updated']);
    }
}
