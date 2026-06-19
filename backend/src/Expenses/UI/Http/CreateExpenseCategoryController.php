<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\CreateExpenseCategoryCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateExpenseCategoryController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/categories', name: 'api_expense_categories_create', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $id = $this->commandBus->dispatch(new CreateExpenseCategoryCommand(
            $householdId,
            (string) ($payload['name'] ?? ''),
            (string) ($payload['color'] ?? '#2457ff'),
        ));

        return new JsonResponse(['id' => $id], JsonResponse::HTTP_CREATED);
    }
}
