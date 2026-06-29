<?php

namespace App\Shared\UI\Http;

use App\Identity\Application\Security\HouseholdAccess;
use App\Shared\Application\Audit\AuditLogger;
use App\Shared\Application\Export\HouseholdExportBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ExportHouseholdController
{
    public function __construct(
        private HouseholdAccess $householdAccess,
        private HouseholdExportBuilder $exportBuilder,
        private AuditLogger $audit,
    ) {
    }

    #[Route('/api/households/{householdId}/export', name: 'api_household_export', methods: ['GET'])]
    public function __invoke(string $householdId): Response
    {
        $actor = $this->householdAccess->assertCanAccess($householdId);
        $export = $this->exportBuilder->build($householdId);
        $payload = json_encode($export, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        $this->audit->record($householdId, $actor, 'household_export', $householdId, 'download', 'Household export downloaded.', [
            'format' => $export['format'],
            'attachmentsIncluded' => false,
        ]);

        return new Response($payload, Response::HTTP_OK, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="home-os-export-%s.json"', preg_replace('/[^A-Za-z0-9_-]/', '', $householdId) ?: 'household'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
