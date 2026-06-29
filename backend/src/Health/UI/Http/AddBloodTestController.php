<?php

namespace App\Health\UI\Http;

use App\Health\Application\Command\AddBloodTestCommand;
use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Command\CommandBus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddBloodTestController
{
    public function __construct(
        private CommandBus $commandBus,
        private HouseholdAccess $householdAccess,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/health/blood-tests', name: 'api_health_blood_tests_add', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        try {
            $id = $this->commandBus->dispatch(new AddBloodTestCommand(
                $householdId,
                (string) ($payload['memberId'] ?? ''),
                (string) ($payload['testedAt'] ?? date('Y-m-d')),
                isset($payload['labName']) && trim((string) $payload['labName']) !== '' ? (string) $payload['labName'] : null,
                isset($payload['notes']) && trim((string) $payload['notes']) !== '' ? (string) $payload['notes'] : null,
                is_array($payload['markers'] ?? null) ? $payload['markers'] : [],
                isset($payload['sourceDocumentId']) && trim((string) $payload['sourceDocumentId']) !== '' ? (string) $payload['sourceDocumentId'] : null,
            ));
            $this->audit->record($householdId, $actor, 'blood_test', $id, 'create', 'Blood test created.', [
                'testedAt' => (string) ($payload['testedAt'] ?? date('Y-m-d')),
                'markerCount' => is_array($payload['markers'] ?? null) ? count($payload['markers']) : 0,
            ]);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['id' => $id], JsonResponse::HTTP_CREATED);
    }
}
