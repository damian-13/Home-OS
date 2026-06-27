<?php

namespace App\Inbox\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Inbox\Application\Query\GetInboxQuery;
use App\Shared\Application\Query\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetInboxController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/households/{householdId}/inbox', name: 'api_inbox_get', methods: ['GET'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->queryBus->ask(new GetInboxQuery($householdId)));
    }
}
