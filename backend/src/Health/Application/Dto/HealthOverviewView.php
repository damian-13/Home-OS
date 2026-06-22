<?php

namespace App\Health\Application\Dto;

final readonly class HealthOverviewView
{
    /**
     * @param list<BloodTestView> $latestBloodTests
     * @param list<BloodTestMarkerView> $outOfRangeMarkers
     * @param list<string> $markerNames
     */
    public function __construct(
        public ?string $memberId,
        public array $latestBloodTests,
        public array $outOfRangeMarkers,
        public array $markerNames,
    ) {
    }
}
