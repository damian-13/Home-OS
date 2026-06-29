<?php

namespace App\Reminders\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Reminders\Application\Command\UpdateReminderCommand;
use App\Reminders\Domain\Model\Reminder;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateReminderController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/reminders/{reminderId}', name: 'api_reminders_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $reminderId, Request $request): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->commandBus->dispatch(new UpdateReminderCommand(
            $householdId,
            $reminderId,
            (string) ($payload['title'] ?? ''),
            isset($payload['note']) && trim((string) $payload['note']) !== '' ? (string) $payload['note'] : null,
            (string) ($payload['dueAt'] ?? date('Y-m-d')),
            (string) ($payload['recurrenceType'] ?? Reminder::RECURRENCE_NONE),
            isset($payload['relatedType']) && trim((string) $payload['relatedType']) !== '' ? (string) $payload['relatedType'] : null,
            isset($payload['relatedId']) && trim((string) $payload['relatedId']) !== '' ? (string) $payload['relatedId'] : null,
            (string) ($payload['priority'] ?? Reminder::PRIORITY_NORMAL),
        ));
        $this->audit->record($householdId, $actor, 'reminder', $reminderId, 'update', 'Reminder updated.', [
            'title' => (string) ($payload['title'] ?? ''),
            'dueAt' => (string) ($payload['dueAt'] ?? date('Y-m-d')),
        ]);

        return new JsonResponse(['status' => 'updated']);
    }
}
