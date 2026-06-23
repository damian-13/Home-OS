<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\UpdateIncomeSourceCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateIncomeSourceController
{
    public function __construct(private CommandBus $commandBus, private HouseholdAccess $householdAccess) {}

    #[Route('/api/households/{householdId}/expenses/income-sources/{sourceId}', name: 'api_income_sources_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $sourceId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $id = $this->commandBus->dispatch(new UpdateIncomeSourceCommand(
            $householdId,
            $sourceId,
            ($payload['memberId'] ?? null) ? (string) $payload['memberId'] : null,
            (string) ($payload['name'] ?? ''),
            (float) ($payload['amount'] ?? 0),
            (bool) ($payload['active'] ?? true),
        ));
        return new JsonResponse(['id' => $id]);
    }
}
