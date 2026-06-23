<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\AddIncomeSourceCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddIncomeSourceController
{
    public function __construct(private CommandBus $commandBus, private HouseholdAccess $householdAccess) {}

    #[Route('/api/households/{householdId}/expenses/income-sources', name: 'api_income_sources_add', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $id = $this->commandBus->dispatch(new AddIncomeSourceCommand(
            $householdId,
            ($payload['memberId'] ?? null) ? (string) $payload['memberId'] : null,
            (string) ($payload['name'] ?? ''),
            (float) ($payload['amount'] ?? 0),
        ));
        return new JsonResponse(['id' => $id], JsonResponse::HTTP_CREATED);
    }
}
