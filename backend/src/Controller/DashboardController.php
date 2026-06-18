<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController
{
    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'app' => 'Home OS',
            'status' => 'online',
            'summary' => [
                'homeTasksDue' => 3,
                'monthlySpend' => 1284.5,
                'healthMarkersTracked' => 12,
                'documentsStored' => 24,
            ],
            'attention' => [
                ['label' => 'Boiler service', 'area' => 'Home', 'due' => 'This week'],
                ['label' => 'Review groceries budget', 'area' => 'Expenses', 'due' => 'Today'],
                ['label' => 'Add latest blood test', 'area' => 'Health', 'due' => 'Next'],
            ],
        ]);
    }
}
