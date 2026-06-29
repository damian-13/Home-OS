<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\UpdateExpenseCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateExpenseController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/{expenseId}', name: 'api_expenses_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $expenseId, Request $request): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $id = $this->commandBus->dispatch(new UpdateExpenseCommand(
            $householdId,
            $expenseId,
            (string) ($payload['categoryId'] ?? ''),
            (string) ($payload['description'] ?? ''),
            (float) ($payload['amount'] ?? 0),
            (string) ($payload['spentOn'] ?? date('Y-m-d')),
            ($payload['paidByMemberId'] ?? null) ? (string) $payload['paidByMemberId'] : null,
            isset($payload['reviewStatus']) ? (string) $payload['reviewStatus'] : null,
            isset($payload['reviewReason']) && trim((string) $payload['reviewReason']) !== '' ? (string) $payload['reviewReason'] : null,
        ));
        $this->audit->record($householdId, $actor, 'expense', $id, 'update', 'Expense updated.', [
            'description' => (string) ($payload['description'] ?? ''),
            'reviewStatus' => isset($payload['reviewStatus']) ? (string) $payload['reviewStatus'] : null,
        ]);

        return new JsonResponse(['id' => $id]);
    }
}
