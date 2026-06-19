<?php

namespace App\Household\Application\Dto;

use App\Household\Domain\Model\Household;

final readonly class HouseholdView
{
    /**
     * @param list<HouseholdMemberView> $members
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $defaultCurrency,
        public string $createdAt,
        public array $members,
    ) {
    }

    public static function fromHousehold(Household $household): self
    {
        return new self(
            $household->id(),
            $household->name(),
            $household->defaultCurrency(),
            $household->createdAt()->format(DATE_ATOM),
            array_map(HouseholdMemberView::fromMember(...), $household->members()),
        );
    }
}
