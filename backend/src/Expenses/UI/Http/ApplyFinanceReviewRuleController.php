<?php

namespace App\Expenses\UI\Http;

use App\Expenses\Application\Command\ApplyFinanceReviewRuleCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ApplyFinanceReviewRuleController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/expenses/review-rules/apply', name: 'api_finance_review_rules_apply', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return new JsonResponse($this->commandBus->dispatch(new ApplyFinanceReviewRuleCommand(
            $householdId,
            (string) ($payload['targetType'] ?? ''),
            (string) ($payload['matchText'] ?? ''),
            (string) ($payload['month'] ?? date('Y-m')),
            isset($payload['categoryId']) && $payload['categoryId'] ? (string) $payload['categoryId'] : null,
            isset($payload['incomeKind']) && $payload['incomeKind'] ? (string) $payload['incomeKind'] : null,
        )));
    }
}
