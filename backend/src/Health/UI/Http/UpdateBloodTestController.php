<?php

namespace App\Health\UI\Http;

use App\Health\Application\Command\UpdateBloodTestCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Command\CommandBus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateBloodTestController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
    ) {
    }

    #[Route('/api/households/{householdId}/health/blood-tests/{bloodTestId}', name: 'api_health_blood_tests_update', methods: ['PATCH'])]
    public function __invoke(string $householdId, string $bloodTestId, Request $request): JsonResponse
    {
        $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        try {
            $id = $this->commandBus->dispatch(new UpdateBloodTestCommand(
                $householdId,
                $bloodTestId,
                (string) ($payload['memberId'] ?? ''),
                (string) ($payload['testedAt'] ?? date('Y-m-d')),
                isset($payload['labName']) && trim((string) $payload['labName']) !== '' ? (string) $payload['labName'] : null,
                isset($payload['notes']) && trim((string) $payload['notes']) !== '' ? (string) $payload['notes'] : null,
                is_array($payload['markers'] ?? null) ? $payload['markers'] : [],
            ));
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['id' => $id]);
    }
}
