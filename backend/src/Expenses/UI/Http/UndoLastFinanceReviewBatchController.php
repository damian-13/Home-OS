<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\UndoLastFinanceReviewBatchCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UndoLastFinanceReviewBatchController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/review-batches/undo-last', name: 'api_finance_review_batches_undo_last', methods: ['POST'])]
    public function __invoke(string $householdId): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);

        return new JsonResponse($this->commandBus->dispatch(new UndoLastFinanceReviewBatchCommand($householdId)));
    }
}
