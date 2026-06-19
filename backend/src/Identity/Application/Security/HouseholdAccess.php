<?php

namespace App\Identity\Application\Security;

use App\Identity\Domain\Model\UserAccount;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class HouseholdAccess
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function assertCanAccess(string $householdId): UserAccount
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserAccount) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        if ($user->householdId() !== $householdId) {
            throw new AccessDeniedHttpException('You cannot access this household.');
        }

        return $user;
    }
}
