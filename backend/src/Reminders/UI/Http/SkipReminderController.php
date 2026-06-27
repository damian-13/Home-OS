<?php

namespace App\Reminders\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Reminders\Application\Command\SkipReminderCommand;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SkipReminderController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/reminders/{reminderId}/skip', name: 'api_reminders_skip', methods: ['POST'])]
    public function __invoke(string $householdId, string $reminderId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new SkipReminderCommand($householdId, $reminderId));

        return new JsonResponse(['status' => 'skipped']);
    }
}
