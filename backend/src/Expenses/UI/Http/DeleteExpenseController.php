<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\DeleteExpenseCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteExpenseController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/{expenseId}', name: 'api_expenses_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $expenseId): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new DeleteExpenseCommand($householdId, $expenseId));
        $this->audit->record($householdId, $actor, 'expense', $expenseId, 'delete', 'Expense deleted.');

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
