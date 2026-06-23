<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\DeleteIncomeSourceCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteIncomeSourceController
{
    public function __construct(private CommandBus $commandBus, private HouseholdAccess $householdAccess) {}

    #[Route('/api/households/{householdId}/expenses/income-sources/{sourceId}', name: 'api_income_sources_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $sourceId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $this->commandBus->dispatch(new DeleteIncomeSourceCommand($householdId, $sourceId));
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
