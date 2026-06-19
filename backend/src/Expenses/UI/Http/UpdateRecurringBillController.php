<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\UpdateRecurringBillCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateRecurringBillController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/recurring-bills/{billId}', name: 'api_recurring_bills_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $billId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $id = $this->commandBus->dispatch(new UpdateRecurringBillCommand(
            $householdId,
            $billId,
            (string) ($payload['categoryId'] ?? ''),
            (string) ($payload['name'] ?? ''),
            (float) ($payload['amount'] ?? 0),
            (int) ($payload['dueDay'] ?? 1),
            ($payload['paidByMemberId'] ?? null) ? (string) $payload['paidByMemberId'] : null,
        ));

        return new JsonResponse(['id' => $id]);
    }
}
