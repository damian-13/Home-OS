<?php

namespace App\Household\Application\Query;

use App\Household\Application\Dto\HouseholdMemberView;
use App\Household\Domain\Repository\HouseholdRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class ListHouseholdMembersHandler implements QueryHandler
{
    public function __construct(
        private HouseholdRepository $households,
    ) {
    }

    public function handles(): string
    {
        return ListHouseholdMembersQuery::class;
    }

    /**
     * @return list<HouseholdMemberView>
     */
    public function __invoke(ListHouseholdMembersQuery $query): array
    {
        return array_map(
            HouseholdMemberView::fromMember(...),
            $this->households->get($query->householdId)->members(),
        );
    }
}
