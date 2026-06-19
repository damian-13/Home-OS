<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\AddRecurringBillCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddRecurringBillController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/recurring-bills', name: 'api_recurring_bills_add', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $id = $this->commandBus->dispatch(new AddRecurringBillCommand(
            $householdId,
            (string) ($payload['categoryId'] ?? ''),
            (string) ($payload['name'] ?? ''),
            (float) ($payload['amount'] ?? 0),
            (int) ($payload['dueDay'] ?? 1),
            ($payload['paidByMemberId'] ?? null) ? (string) $payload['paidByMemberId'] : null,
        ));

        return new JsonResponse(['id' => $id], JsonResponse::HTTP_CREATED);
    }
}
