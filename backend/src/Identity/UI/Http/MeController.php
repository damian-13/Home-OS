<?php

namespace App\Identity\UI\Http;

use App\Identity\Application\Dto\CurrentUserView;
use App\Identity\Domain\Model\UserAccount;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MeController
{
    public function __construct(
        private Security $security,
    ) {
    }

    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserAccount) {
            return new JsonResponse(['user' => null]);
        }

        return new JsonResponse(['user' => CurrentUserView::fromUser($user)]);
    }
}
