<?php

namespace App\Reminders\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Reminders\Application\Command\DeleteReminderCommand;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteReminderController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/reminders/{reminderId}', name: 'api_reminders_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $reminderId): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new DeleteReminderCommand($householdId, $reminderId));
        $this->audit->record($householdId, $actor, 'reminder', $reminderId, 'delete', 'Reminder deleted.');

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
