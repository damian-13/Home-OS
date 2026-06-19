<?php

namespace App\Identity\UI\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LoginController
{
    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['message' => 'Login is handled by the security firewall.']);
    }
}
