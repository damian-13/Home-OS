<?php

namespace App\Documents\Domain\Repository;

use App\Documents\Domain\Model\Document;
use DateTimeImmutable;

interface DocumentRepository
{
    public function save(Document $document): void;

    public function get(string $householdId, string $documentId): Document;

    /**
     * @return list<Document>
     */
    public function documentsForHousehold(string $householdId): array;

    /**
     * @return list<Document>
     */
    public function expiredDocuments(string $householdId, DateTimeImmutable $today, int $limit = 10): array;

    /**
     * @return list<Document>
     */
    public function expiringDocuments(string $householdId, DateTimeImmutable $today, int $days = 30, int $limit = 10): array;

    public function countDocuments(string $householdId): int;
}
