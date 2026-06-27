<?php

namespace App\Reminders\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Reminders\Application\Query\ListRemindersQuery;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListRemindersController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/households/{householdId}/reminders', name: 'api_reminders_list', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse([
            'reminders' => $this->queryBus->ask(new ListRemindersQuery($householdId)),
        ]);
    }
}
