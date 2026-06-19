<?php

namespace App\Household\Application\Dto;

use App\Household\Domain\Model\HouseholdMember;

final readonly class HouseholdMemberView
{
    public function __construct(
        public string $id,
        public string $displayName,
        public string $memberType,
        public ?string $birthDate,
        public ?string $color,
        public bool $active,
    ) {
    }

    public static function fromMember(HouseholdMember $member): self
    {
        return new self(
            $member->id(),
            $member->displayName(),
            $member->memberType(),
            $member->birthDate()?->format('Y-m-d'),
            $member->color(),
            $member->active(),
        );
    }
}
