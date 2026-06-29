<?php

namespace App\Reminders\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Reminders\Application\Command\CompleteReminderCommand;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CompleteReminderController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/reminders/{reminderId}/complete', name: 'api_reminders_complete', methods: ['POST'])]
    public function __invoke(string $householdId, string $reminderId): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new CompleteReminderCommand($householdId, $reminderId));
        $this->audit->record($householdId, $actor, 'reminder', $reminderId, 'complete', 'Reminder completed.');

        return new JsonResponse(['status' => 'completed']);
    }
}
