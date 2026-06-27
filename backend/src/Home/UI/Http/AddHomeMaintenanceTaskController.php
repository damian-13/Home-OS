<?php

namespace App\Home\UI\Http;

use App\Home\Application\Command\AddHomeMaintenanceTaskCommand;
use App\Home\Domain\Model\HomeMaintenanceTask;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddHomeMaintenanceTaskController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/home/maintenance-tasks', name: 'api_home_maintenance_tasks_add', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $id = $this->commandBus->dispatch(new AddHomeMaintenanceTaskCommand(
            $householdId,
            (string) ($payload['title'] ?? ''),
            (string) ($payload['area'] ?? ''),
            (string) ($payload['nextDueAt'] ?? date('Y-m-d')),
            (string) ($payload['recurrenceType'] ?? HomeMaintenanceTask::RECURRENCE_NONE),
            isset($payload['assignedMemberId']) && trim((string) $payload['assignedMemberId']) !== '' ? (string) $payload['assignedMemberId'] : null,
            (string) ($payload['priority'] ?? HomeMaintenanceTask::PRIORITY_NORMAL),
            isset($payload['notes']) && trim((string) $payload['notes']) !== '' ? (string) $payload['notes'] : null,
        ));

        return new JsonResponse(['id' => $id], 201);
    }
}
