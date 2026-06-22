<?php

namespace App\Health\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class AddBloodTestCommand implements Command
{
    /**
     * @param list<array{name?: string, markerName?: string, value: float|int|string, unit: string, referenceMin?: float|int|string|null, referenceMax?: float|int|string|null, status?: string|null, notes?: string|null}> $markers
     */
    public function __construct(
        public string $householdId,
        public string $memberId,
        public string $testedAt,
        public ?string $labName,
        public ?string $notes,
        public array $markers,
        public ?string $sourceDocumentId = null,
    ) {
    }
}
