<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\DeleteRecurringBillCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteRecurringBillController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/recurring-bills/{billId}', name: 'api_recurring_bills_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $billId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new DeleteRecurringBillCommand($householdId, $billId));

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
