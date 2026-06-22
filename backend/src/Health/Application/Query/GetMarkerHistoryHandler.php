<?php

namespace App\Health\Application\Query;

use App\Health\Application\Dto\BloodTestMarkerView;
use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class GetMarkerHistoryHandler implements QueryHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return GetMarkerHistoryQuery::class;
    }

    /**
     * @return list<BloodTestMarkerView>
     */
    public function __invoke(GetMarkerHistoryQuery $query): array
    {
        return array_map(
            static fn ($marker) => BloodTestMarkerView::fromMarker($marker),
            $this->health->markerHistory($query->householdId, $query->markerName, $query->memberId),
        );
    }
}
