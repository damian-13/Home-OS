<?php

namespace App\Health\Application\Query;

use App\Health\Application\Dto\BloodTestMarkerView;
use App\Health\Application\Dto\BloodTestView;
use App\Health\Application\Dto\HealthOverviewView;
use App\Health\Application\DefaultHealthMarkers;
use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class GetHealthOverviewHandler implements QueryHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return GetHealthOverviewQuery::class;
    }

    public function __invoke(GetHealthOverviewQuery $query): HealthOverviewView
    {
        return new HealthOverviewView(
            $query->memberId,
            array_map(static fn ($bloodTest) => BloodTestView::fromBloodTest($bloodTest), $this->health->latestBloodTests($query->householdId, $query->memberId)),
            array_map(static fn ($marker) => BloodTestMarkerView::fromMarker($marker), $this->health->latestOutOfRangeMarkers($query->householdId, $query->memberId)),
            $this->health->markerNames($query->householdId, $query->memberId),
            DefaultHealthMarkers::all(),
        );
    }
}
