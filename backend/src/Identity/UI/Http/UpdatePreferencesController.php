<?php

namespace App\Identity\UI\Http;

use App\Identity\Application\Dto\CurrentUserView;
use App\Identity\Domain\Model\UserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdatePreferencesController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/auth/me/preferences', name: 'api_auth_me_preferences', methods: ['PATCH'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserAccount) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Request body must be valid JSON.');
        }

        if (array_key_exists('language', $payload)) {
            $language = (string) $payload['language'];

            if (!in_array($language, ['en', 'pl'], true)) {
                throw new BadRequestHttpException('Language must be en or pl.');
            }

            $user->changeLanguage($language);
        }

        $this->entityManager->flush();

        return new JsonResponse(['user' => CurrentUserView::fromUser($user)]);
    }
}
