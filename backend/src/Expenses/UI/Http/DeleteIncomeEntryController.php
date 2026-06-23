<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\DeleteIncomeEntryCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteIncomeEntryController
{
    public function __construct(private CommandBus $commandBus, private HouseholdAccess $householdAccess) {}

    #[Route('/api/households/{householdId}/expenses/income-entries/{entryId}', name: 'api_income_entries_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $entryId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new DeleteIncomeEntryCommand($householdId, $entryId));
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
