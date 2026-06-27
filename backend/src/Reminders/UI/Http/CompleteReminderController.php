<?php

namespace App\Reminders\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Reminders\Application\Command\CompleteReminderCommand;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CompleteReminderController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/reminders/{reminderId}/complete', name: 'api_reminders_complete', methods: ['POST'])]
    public function __invoke(string $householdId, string $reminderId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new CompleteReminderCommand($householdId, $reminderId));

        return new JsonResponse(['status' => 'completed']);
    }
}
