<?php

namespace App\Identity\UI\Http;

use App\Household\Domain\Model\Household;
use App\Identity\Domain\Model\UserAccount;
use App\Shared\Domain\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RegisterController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->payload($request);

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');
        $displayName = trim((string) ($payload['displayName'] ?? ''));
        $householdName = trim((string) ($payload['householdName'] ?? 'Home OS Household'));
        $language = (string) ($payload['language'] ?? 'en');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Valid email is required.');
        }

        if (strlen($password) < 8) {
            throw new BadRequestHttpException('Password must contain at least 8 characters.');
        }

        if ('' === $displayName) {
            throw new BadRequestHttpException('Display name is required.');
        }

        if (!in_array($language, ['en', 'pl'], true)) {
            throw new BadRequestHttpException('Language must be en or pl.');
        }

        if ($this->entityManager->getRepository(UserAccount::class)->findOneBy(['email' => $email])) {
            throw new BadRequestHttpException('Account with this email already exists.');
        }

        $household = new Household((string) Uuid::new(), $householdName, 'PLN');
        $member = $household->addMember((string) Uuid::new(), $displayName, 'adult', null, '#175c4a');
        $user = new UserAccount(
            (string) Uuid::new(),
            $email,
            '',
            $displayName,
            $household->id(),
            $member->id(),
        );
        $passwordHash = $this->passwordHasher->hashPassword($user, $password);

        $user = new UserAccount(
            $user->id(),
            $email,
            $passwordHash,
            $displayName,
            $household->id(),
            $member->id(),
        );
        $user->changeLanguage($language);

        $this->entityManager->persist($household);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $user->id(),
            'email' => $user->email(),
            'householdId' => $user->householdId(),
            'linkedMemberId' => $user->linkedMemberId(),
            'language' => $user->language(),
        ], JsonResponse::HTTP_CREATED);
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
