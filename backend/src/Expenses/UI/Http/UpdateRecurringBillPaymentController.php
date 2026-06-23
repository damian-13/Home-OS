<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\UpdateRecurringBillPaymentCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateRecurringBillPaymentController
{
    public function __construct(private CommandBus $commandBus, private HouseholdAccess $householdAccess) {}

    #[Route('/api/households/{householdId}/expenses/recurring-bills/{billId}/payments/{month}', name: 'api_recurring_bill_payments_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $billId, string $month, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $id = $this->commandBus->dispatch(new UpdateRecurringBillPaymentCommand(
            $householdId,
            $billId,
            $month,
            (string) ($payload['status'] ?? 'planned'),
            ($payload['paidOn'] ?? null) ? (string) $payload['paidOn'] : null,
            isset($payload['amount']) && $payload['amount'] !== null && $payload['amount'] !== '' ? (float) $payload['amount'] : null,
        ));
        return new JsonResponse(['id' => $id]);
    }
}
