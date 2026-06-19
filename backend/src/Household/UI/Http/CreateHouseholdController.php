<?php

namespace App\Household\UI\Http;

use App\Household\Application\Command\CreateHouseholdCommand;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateHouseholdController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households', name: 'api_households_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $name = trim((string) ($payload['name'] ?? ''));

        if ('' === $name) {
            throw new BadRequestHttpException('Household name is required.');
        }

        $householdId = $this->commandBus->dispatch(new CreateHouseholdCommand(
            $name,
            (string) ($payload['defaultCurrency'] ?? 'PLN'),
        ));

        return new JsonResponse(['id' => $householdId], JsonResponse::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Request body must be valid JSON.');
        }

        return $payload;
    }
}
