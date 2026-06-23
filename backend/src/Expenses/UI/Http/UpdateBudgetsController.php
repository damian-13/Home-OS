<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\UpdateBudgetsCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateBudgetsController
{
    public function __construct(private CommandBus $commandBus, private HouseholdAccess $householdAccess) {}

    #[Route('/api/households/{householdId}/expenses/budgets/{month}', name: 'api_expense_budgets_update', methods: ['PUT'])]
    public function __invoke(string $householdId, string $month, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->commandBus->dispatch(new UpdateBudgetsCommand(
            $householdId,
            $month,
            is_array($payload['budgets'] ?? null) ? $payload['budgets'] : [],
        ));
        return new JsonResponse(['month' => $month]);
    }
}
