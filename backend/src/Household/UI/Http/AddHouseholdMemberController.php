<?php

namespace App\Household\UI\Http;

use App\Household\Application\Command\AddHouseholdMemberCommand;
use App\Household\Domain\Model\HouseholdMember;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddHouseholdMemberController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    #[Route('/api/households/{householdId}/members', name: 'api_household_members_add', methods: ['POST'])]
    public function __invoke(string $householdId, Request $request): JsonResponse
    {
        $payload = $this->payload($request);
        $displayName = trim((string) ($payload['displayName'] ?? ''));
        $memberType = (string) ($payload['memberType'] ?? HouseholdMember::TYPE_ADULT);

        if ('' === $displayName) {
            throw new BadRequestHttpException('Member display name is required.');
        }

        if (!in_array($memberType, [HouseholdMember::TYPE_ADULT, HouseholdMember::TYPE_CHILD], true)) {
            throw new BadRequestHttpException('Member type must be "adult" or "child".');
        }

        $memberId = $this->commandBus->dispatch(new AddHouseholdMemberCommand(
            $householdId,
            $displayName,
            $memberType,
            isset($payload['birthDate']) && '' !== $payload['birthDate'] ? (string) $payload['birthDate'] : null,
            isset($payload['color']) && '' !== $payload['color'] ? (string) $payload['color'] : null,
        ));

        return new JsonResponse(['id' => $memberId], JsonResponse::HTTP_CREATED);
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
