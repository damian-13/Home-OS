<?php

namespace App\Household\Application\Query;

use App\Household\Application\Dto\HouseholdView;
use App\Household\Domain\Repository\HouseholdRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class GetHouseholdHandler implements QueryHandler
{
    public function __construct(
        private HouseholdRepository $households,
    ) {
    }

    public function handles(): string
    {
        return GetHouseholdQuery::class;
    }

    public function __invoke(GetHouseholdQuery $query): HouseholdView
    {
        return HouseholdView::fromHousehold($this->households->get($query->householdId));
    }
}
