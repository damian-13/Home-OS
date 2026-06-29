<?php

namespace App\Health\UI\Http;

use App\Health\Application\Command\DeleteBloodTestCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteBloodTestController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/health/blood-tests/{bloodTestId}', name: 'api_health_blood_tests_delete', methods: ['DELETE'])]
    public function __invoke(string $householdId, string $bloodTestId): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);

        try {
            $this->commandBus->dispatch(new DeleteBloodTestCommand($householdId, $bloodTestId));
            $this->audit->record($householdId, $actor, 'blood_test', $bloodTestId, 'delete', 'Blood test deleted.');
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
