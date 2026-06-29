<?php

namespace App\Reminders\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Reminders\Application\Command\SkipReminderCommand;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SkipReminderController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/reminders/{reminderId}/skip', name: 'api_reminders_skip', methods: ['POST'])]
    public function __invoke(string $householdId, string $reminderId): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new SkipReminderCommand($householdId, $reminderId));
        $this->audit->record($householdId, $actor, 'reminder', $reminderId, 'skip', 'Reminder skipped.');

        return new JsonResponse(['status' => 'skipped']);
    }
}
