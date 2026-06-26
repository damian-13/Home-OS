<?php

namespace App\Controller;

use App\Dashboard\Application\Query\GetDashboardQuery;
use App\Identity\Domain\Model\UserAccount;
use App\Shared\Application\Query\QueryBus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DashboardController
{
    public function __construct(
        private Security $security,
        private QueryBus $queryBus,
    ) {
    }

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserAccount) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        return new JsonResponse($this->queryBus->ask(new GetDashboardQuery($user->householdId())));
    }
}
