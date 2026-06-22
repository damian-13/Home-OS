<?php

namespace App\Health\Domain\Repository;

use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Model\BloodTestMarker;
use App\Health\Domain\Model\HealthDocument;

interface HealthRepository
{
    public function saveBloodTest(BloodTest $bloodTest): void;

    public function saveDocument(HealthDocument $document): void;

    /**
     * @return list<HealthDocument>
     */
    public function latestDocuments(string $householdId, ?string $memberId = null, int $limit = 20): array;

    public function documentById(string $householdId, string $documentId): ?HealthDocument;

    /**
     * @return list<BloodTest>
     */
    public function latestBloodTests(string $householdId, ?string $memberId = null, int $limit = 10): array;

    /**
     * @return list<BloodTestMarker>
     */
    public function latestOutOfRangeMarkers(string $householdId, ?string $memberId = null, int $limit = 10): array;

    /**
     * @return list<BloodTestMarker>
     */
    public function markerHistory(string $householdId, string $markerName, ?string $memberId = null, int $limit = 20): array;

    /**
     * @return list<string>
     */
    public function markerNames(string $householdId, ?string $memberId = null): array;
}
