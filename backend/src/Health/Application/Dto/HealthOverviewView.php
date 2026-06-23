<?php

namespace App\Health\Application\Dto;

final readonly class HealthOverviewView
{
    /**
     * @param list<BloodTestView> $latestBloodTests
     * @param list<BloodTestMarkerView> $outOfRangeMarkers
     * @param list<string> $markerNames
     * @param list<array{name: string, aliases: list<string>, unit: string, referenceMin: float|null, referenceMax: float|null, category: string}> $markerCatalog
     */
    public function __construct(
        public ?string $memberId,
        public array $latestBloodTests,
        public array $outOfRangeMarkers,
        public array $markerNames,
        public array $markerCatalog,
    ) {
    }
}
