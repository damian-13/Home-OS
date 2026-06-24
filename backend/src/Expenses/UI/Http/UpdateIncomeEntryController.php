<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\UpdateIncomeEntryCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateIncomeEntryController
{
    public function __construct(private CommandBus $commandBus, private HouseholdAccess $householdAccess) {}

    #[Route('/api/households/{householdId}/expenses/income-entries/{entryId}', name: 'api_income_entries_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $entryId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $id = $this->commandBus->dispatch(new UpdateIncomeEntryCommand(
            $householdId,
            $entryId,
            ($payload['sourceId'] ?? null) ? (string) $payload['sourceId'] : null,
            ($payload['memberId'] ?? null) ? (string) $payload['memberId'] : null,
            (string) ($payload['description'] ?? ''),
            (float) ($payload['amount'] ?? 0),
            (string) ($payload['receivedOn'] ?? date('Y-m-d')),
            isset($payload['incomeKind']) ? (string) $payload['incomeKind'] : null,
            isset($payload['reviewStatus']) ? (string) $payload['reviewStatus'] : null,
            isset($payload['reviewReason']) && trim((string) $payload['reviewReason']) !== '' ? (string) $payload['reviewReason'] : null,
        ));
        return new JsonResponse(['id' => $id]);
    }
}
